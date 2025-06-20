<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $notifications = Auth::user()->notifications()
                            ->with('chantier')
                            ->orderBy('created_at', 'desc')
                            ->paginate(20);
        
        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            abort(403);
        }
        
        $notification->marquerLue();
        
        return redirect()->back();
    }

    public function markAllAsRead()
    {
        Auth::user()->notifications()
            ->where('lu', false)
            ->update(['lu' => true, 'lu_at' => now()]);
        
        return redirect()->back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Voir une notification et la marquer comme lue
     */
    public function viewAndMarkAsRead(Notification $notification)
    {
        // Vérifier les autorisations
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Accès non autorisé à cette notification.');
        }
        
        // Marquer comme lue si elle ne l'est pas déjà
        if (!$notification->lu) {
            $notification->marquerLue();
        }
        
        // Rediriger vers le chantier si disponible
        if ($notification->chantier) {
            return redirect()->route('chantiers.show', $notification->chantier)
                            ->with('success', 'Notification marquée comme lue.');
        }
        
        // Sinon retourner aux notifications avec un message
        return redirect()->route('notifications.index')
                        ->with('success', 'Notification marquée comme lue.');
    }
    public function view(Notification $notification)
{
    if ($notification->user_id !== Auth::id()) {
        abort(403);
    }
    
    // Marquer comme lue si pas encore lu
    if (!$notification->lu) {
        $notification->marquerLue();
    }
    
    // Rediriger vers le chantier si la notification en a un
    if ($notification->chantier_id) {
        return redirect()->route('chantiers.show', $notification->chantier_id);
    }
    
    // Sinon retourner au dashboard
    return redirect()->route('dashboard');
}
}

