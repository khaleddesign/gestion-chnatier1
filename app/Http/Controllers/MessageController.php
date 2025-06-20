<?php

namespace App\Http\Controllers;

use App\Mail\NewMessage;
use App\Models\Chantier;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class MessageController extends Controller
{
    /**
     * Constructeur : vérification de l'authentification
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche la liste des messages reçus.
     */
    public function index()
    {
        $messages = Auth::user()->receivedMessages()
            ->with(['sender', 'chantier'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $unreadCount = Auth::user()->getUnreadMessagesCount();

        return view('messages.index', compact('messages', 'unreadCount'));
    }

    /**
     * Affiche la liste des messages envoyés.
     */
    public function sent()
    {
        $messages = Auth::user()->sentMessages()
            ->with(['recipient', 'chantier'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('messages.sent', compact('messages'));
    }

    /**
     * Affiche le formulaire pour créer un nouveau message.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $recipientId = $request->input('recipient_id');
        $chantierId = $request->input('chantier_id');
        $subject = $request->input('subject', '');
        
        // Déterminer les utilisateurs éligibles comme destinataires selon le rôle
        if ($user->isAdmin()) {
            $recipients = User::where('id', '!=', $user->id)->where('active', true)->get();
        } elseif ($user->isCommercial()) {
            $recipients = User::where('role', 'client')->where('active', true)
                ->orWhere('role', 'admin')
                ->where('id', '!=', $user->id)
                ->get();
        } else { // Client
            $recipients = User::where(function($query) use ($user) {
                $query->where('role', 'commercial')
                    ->orWhere('role', 'admin');
            })
            ->where('active', true)
            ->get();
        }
        
        // Si un chantier est spécifié, on le charge
        $chantier = null;
        if ($chantierId) {
            $chantier = Chantier::find($chantierId);
            // Vérifier que l'utilisateur a accès à ce chantier
            if ($chantier && !$user->can('view', $chantier)) {
                abort(403);
            }
            
            // Pré-remplir le destinataire si c'est un client
            if ($user->isClient() && !$recipientId && $chantier) {
                $recipientId = $chantier->commercial_id;
            }
            // Pré-remplir le destinataire si c'est un commercial
            elseif ($user->isCommercial() && !$recipientId && $chantier) {
                $recipientId = $chantier->client_id;
            }
            
            // Suggérer un sujet si non fourni
            if (!$subject && $chantier) {
                $subject = "À propos du chantier : {$chantier->titre}";
            }
        }

        return view('messages.create', compact('recipients', 'recipientId', 'chantier', 'subject'));
    }

    /**
     * Enregistre un nouveau message.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'chantier_id' => 'nullable|exists:chantiers,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $user = Auth::user();
        $recipient = User::findOrFail($validated['recipient_id']);
        
        // Vérifier que le destinataire a un rôle compatible
        $allowedRecipientRoles = $user->isClient() 
            ? ['commercial', 'admin'] 
            : ($user->isCommercial() ? ['client', 'admin'] : ['admin', 'commercial', 'client']);
            
        if (!in_array($recipient->role, $allowedRecipientRoles)) {
            return back()->withErrors(['recipient_id' => 'Destinataire invalide.'])->withInput();
        }
        
        // Si un chantier est spécifié, vérifier que l'utilisateur y a accès
        if (isset($validated['chantier_id']) && $validated['chantier_id']) {
            $chantier = Chantier::findOrFail($validated['chantier_id']);
            if (!$user->can('view', $chantier)) {
                abort(403);
            }
        }

        // Créer le message
        $message = Message::create([
            'sender_id' => $user->id,
            'recipient_id' => $validated['recipient_id'],
            'chantier_id' => $validated['chantier_id'] ?? null,
            'subject' => $validated['subject'],
            'body' => $validated['body'],
        ]);

        // Envoyer l'email
        try {
            Mail::to($recipient->email)->send(new NewMessage($message));
        } catch (\Exception $e) {
            // Journaliser l'erreur mais ne pas bloquer le processus
            \Log::error("Erreur d'envoi d'email: " . $e->getMessage());
        }

        // Créer une notification pour le destinataire
        Notification::create([
            'user_id' => $validated['recipient_id'],
'chantier_id' => !empty($validated['chantier_id']) ? $validated['chantier_id'] : null,
            'type' => 'nouveau_message',
            'titre' => 'Nouveau message',
            'message' => "Vous avez reçu un nouveau message de {$user->name}: {$validated['subject']}",
            'lu' => false,
        ]);

        return redirect()->route('messages.index')
            ->with('success', 'Message envoyé avec succès');
    }

    /**
     * Affiche un message spécifique.
     */
    public function show(Message $message)
    {
        // Vérifier que l'utilisateur est bien concerné par ce message
        if ($message->sender_id !== Auth::id() && $message->recipient_id !== Auth::id()) {
            abort(403);
        }
        
        // Marquer comme lu si l'utilisateur est le destinataire
        if ($message->recipient_id === Auth::id() && !$message->is_read) {
            $message->markAsRead();
        }
        
        return view('messages.show', compact('message'));
    }

    /**
     * Pré-remplir un formulaire de réponse.
     */
    public function reply(Message $message)
    {
        // Vérifier que l'utilisateur est bien concerné par ce message
        if ($message->sender_id !== Auth::id() && $message->recipient_id !== Auth::id()) {
            abort(403);
        }
        
        // Déterminer le destinataire (l'autre personne)
        $recipientId = $message->sender_id === Auth::id() 
            ? $message->recipient_id 
            : $message->sender_id;
        
        // Préparer le sujet de réponse
        $subject = "Re: " . $message->subject;
        
        return view('messages.create', [
            'recipients' => collect([User::find($recipientId)]),
            'recipientId' => $recipientId,
            'chantier' => $message->chantier,
            'subject' => $subject,
            'originalMessage' => $message,
        ]);
    }

    /**
     * Retourne un modal d'envoi de message pour les requêtes AJAX.
     */
    public function modal(Request $request)
    {
        $recipientId = $request->input('recipient_id');
        $chantierId = $request->input('chantier_id');
        
        // On récupère le destinataire et le chantier si fournis
        $recipient = $recipientId ? User::find($recipientId) : null;
        $chantier = $chantierId ? Chantier::find($chantierId) : null;
        
        // Générer le sujet par défaut si un chantier est fourni
        $subject = $chantier ? "À propos du chantier : {$chantier->titre}" : '';
        
        return view('messages.partials.modal', compact('recipient', 'chantier', 'subject'));
    }
}