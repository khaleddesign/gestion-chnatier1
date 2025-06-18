<?php
// app/Http/Controllers/AdminController.php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chantier;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;


class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_chantiers' => Chantier::count(),
            'chantiers_actifs' => Chantier::where('statut', 'en_cours')->count(),
            'chantiers_termines' => Chantier::where('statut', 'termine')->count(),
            'chantiers_en_retard' => Chantier::whereDate('date_fin_prevue', '<', now())
                                           ->where('statut', '!=', 'termine')
                                           ->count(),
            'notifications_non_lues' => Notification::where('lu', false)->count(),
            'utilisateurs_actifs' => User::where('active', true)->count(),
            'avancement_moyen' => Chantier::avg('avancement_global') ?? 0,
        ];

        // Données pour les graphiques
        $chantiers_recents = Chantier::with(['client', 'commercial'])
                                   ->latest()
                                   ->take(5)
                                   ->get();

        $notifications_recentes = Notification::with(['user', 'chantier'])
                                            ->latest()
                                            ->take(10)
                                            ->get();

                                            return view('dashboard.admin', compact('stats', 'chantiers_recents', 'notifications_recentes'));    }

    public function users(Request $request)
    {
        $query = User::query();

        // Filtres
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('active')) {
            $query->where('active', $request->active === '1');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $users = $query->withCount(['chantiersClient', 'chantiersCommercial'])
                      ->orderBy('name')
                      ->paginate(20)
                      ->withQueryString();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'commerciaux' => User::where('role', 'commercial')->count(),
            'clients' => User::where('role', 'client')->count(),
            'actifs' => User::where('active', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,commercial,client'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'active' => $request->boolean('active', true),
        ]);

        // Créer une notification système (seulement si chantier_id peut être null)
        try {
            // Vérifier si la table notifications accepte chantier_id null
            $notificationData = [
                'user_id' => $user->id,
                'titre' => 'Compte créé',
                'message' => 'Votre compte a été créé avec succès. Bienvenue !',
                'type' => 'compte_cree',
            ];
            
            // Ajouter chantier_id seulement si la colonne existe et est nullable
            if (Schema::hasColumn('notifications', 'chantier_id')) {
                $notificationData['chantier_id'] = null;
            }
            
            Notification::create($notificationData);
        } catch (\Exception $e) {
            // Si la notification échoue, on continue quand même
            Log::warning('Erreur création notification pour nouvel utilisateur: ' . $e->getMessage());
        }

        return redirect()->route('admin.users')
                        ->with('success', 'Utilisateur créé avec succès.');
    }

    public function showUser(User $user)
    {
        $user->load(['chantiersClient', 'chantiersCommercial', 'notifications']);
        
        $stats = [
            'chantiers_client' => $user->chantiersClient()->count(),
            'chantiers_commercial' => $user->chantiersCommercial()->count(),
            'notifications_non_lues' => $user->notifications()->where('lu', false)->count(),
            'derniere_connexion' => $user->updated_at->format('d/m/Y H:i'), // Utiliser updated_at à la place
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,commercial,client'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'active' => ['boolean'],
        ]);

        // Empêcher la désactivation du dernier admin
        if ($user->isAdmin() && !$request->boolean('active') && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return redirect()->back()
                           ->with('error', 'Impossible de désactiver le dernier administrateur actif.');
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'telephone' => $request->telephone,
            'adresse' => $request->adresse,
            'active' => $request->boolean('active', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users')
                        ->with('success', 'Utilisateur modifié avec succès.');
    }

    public function destroyUser(User $user)
    {
        // Empêcher la suppression du dernier admin
        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return redirect()->back()
                           ->with('error', 'Impossible de supprimer le dernier administrateur.');
        }

        // Vérifier s'il y a des chantiers associés
        if ($user->chantiersClient()->count() > 0 || $user->chantiersCommercial()->count() > 0) {
            return redirect()->back()
                           ->with('error', 'Impossible de supprimer un utilisateur ayant des chantiers associés.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('admin.users')
                        ->with('success', "Utilisateur {$userName} supprimé avec succès.");
    }

    public function toggleUser(User $user)
    {
        // Empêcher la désactivation du dernier admin
        if ($user->isAdmin() && $user->active && User::where('role', 'admin')->where('active', true)->count() <= 1) {
            return redirect()->back()
                           ->with('error', 'Impossible de désactiver le dernier administrateur actif.');
        }

        $user->update(['active' => !$user->active]);
        
        $status = $user->active ? 'activé' : 'désactivé';
        return redirect()->back()
                        ->with('success', "Utilisateur {$status} avec succès.");
    }

    public function statistics()
    {
        $stats = [
            'users_by_role' => User::selectRaw('role, COUNT(*) as count')
                                 ->groupBy('role')
                                 ->pluck('count', 'role'),
            'chantiers_by_status' => Chantier::selectRaw('statut, COUNT(*) as count')
                                           ->groupBy('statut')
                                           ->pluck('count', 'statut'),
            'chantiers_by_month' => Chantier::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                                          ->whereYear('created_at', date('Y'))
                                          ->groupBy('month')
                                          ->pluck('count', 'month'),
            'average_progress' => Chantier::avg('avancement_global') ?? 0,
            'users_active_last_month' => User::where('updated_at', '>=', now()->subMonth())->count(), // Utilisé updated_at
            'chantiers_en_retard' => Chantier::whereDate('date_fin_prevue', '<', now())
                                           ->where('statut', '!=', 'termine')
                                           ->count(),
        ];

        // Données pour graphiques avancés
        $monthly_data = DB::table('chantiers')
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total, AVG(avancement_global) as avg_progress')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $performance_data = DB::table('chantiers')
            ->join('users as commercials', 'chantiers.commercial_id', '=', 'commercials.id')
            ->selectRaw('commercials.name, COUNT(*) as total_chantiers, AVG(chantiers.avancement_global) as avg_progress')
            ->groupBy('commercials.id', 'commercials.name')
            ->orderByDesc('total_chantiers')
            ->take(10)
            ->get();

        return view('admin.statistics', compact('stats', 'monthly_data', 'performance_data'));
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:activate,deactivate,delete'],
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $users = User::whereIn('id', $request->user_ids)->get();
        $count = 0;

        foreach ($users as $user) {
            switch ($request->action) {
                case 'activate':
                    if (!$user->active) {
                        $user->update(['active' => true]);
                        $count++;
                    }
                    break;
                    
                case 'deactivate':
                    if ($user->active && !($user->isAdmin() && User::where('role', 'admin')->where('active', true)->count() <= 1)) {
                        $user->update(['active' => false]);
                        $count++;
                    }
                    break;
                    
                case 'delete':
                    if (!($user->isAdmin() && User::where('role', 'admin')->count() <= 1) && 
                        $user->chantiersClient()->count() === 0 && 
                        $user->chantiersCommercial()->count() === 0) {
                        $user->delete();
                        $count++;
                    }
                    break;
            }
        }

        $actionText = match($request->action) {
            'activate' => 'activés',
            'deactivate' => 'désactivés',
            'delete' => 'supprimés',
        };

        return redirect()->back()
                        ->with('success', "{$count} utilisateurs {$actionText} avec succès.");
    }

    public function exportUsers(Request $request)
    {
        $users = User::when($request->role, function($query, $role) {
                        return $query->where('role', $role);
                    })
                    ->when($request->active !== null, function($query) use ($request) {
                        return $query->where('active', $request->active);
                    })
                    ->get();

        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Nom', 'Email', 'Rôle', 'Téléphone', 'Actif', 'Créé le']);
            
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->telephone,
                    $user->active ? 'Oui' : 'Non',
                    $user->created_at->format('d/m/Y H:i'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}