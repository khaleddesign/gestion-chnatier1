<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChantierController;
use App\Http\Controllers\EtapeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MessageController;

// ✅ IMPORTS API avec ALIAS pour éviter les conflits
use App\Http\Controllers\Api\PhotoController as ApiPhotoController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\DevisController as ApiDevisController;
use App\Http\Controllers\Api\NotificationController as ApiNotificationController;
use App\Http\Controllers\Api\EvaluationController as ApiEvaluationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Page d'accueil - Redirect vers dashboard ou login
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Routes d'authentification manuelles (Laravel UI style)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Routes d'inscription (optionnelles)
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

// Routes de réinitialisation de mot de passe (optionnelles)
Route::get('/password/reset', function () {
    return view('auth.passwords.email');
})->name('password.request')->middleware('guest');

Route::post('/password/email', function (Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    
    // Ici vous pouvez implémenter la logique d'envoi d'email
    // Pour l'instant, on retourne juste un message
    return back()->with('status', 'Si cette adresse email existe, vous recevrez un lien de réinitialisation.');
})->name('password.email')->middleware('guest');

// Routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {
    
    // Dashboard principal (route vers le bon dashboard selon le rôle)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home'); // Fallback pour Laravel UI
    
    // ✅ ROUTES SPÉCIFIQUES AVANT LE RESOURCE (ordre CRITIQUE !)
    Route::get('chantiers/export', [ChantierController::class, 'export'])->name('chantiers.export');
    Route::get('chantiers/calendrier/view', [ChantierController::class, 'calendrier'])->name('chantiers.calendrier');
    Route::get('chantiers/search', [ChantierController::class, 'search'])->name('chantiers.search');
    
    // ✅ RESOURCE ROUTE APRÈS (pour éviter les conflits)
    Route::resource('chantiers', ChantierController::class);
    
    // Routes spécifiques avec paramètres (après le resource)
    Route::get('chantiers/{chantier}/etapes', [ChantierController::class, 'etapes'])->name('chantiers.etapes');
    
    // Gestion des étapes (nested routes)
    Route::prefix('chantiers/{chantier}')->group(function () {
        Route::post('etapes', [EtapeController::class, 'store'])->name('etapes.store');
        Route::put('etapes/{etape}', [EtapeController::class, 'update'])->name('etapes.update');
        Route::delete('etapes/{etape}', [EtapeController::class, 'destroy'])->name('etapes.destroy');
        Route::post('etapes/{etape}/toggle', [EtapeController::class, 'toggleComplete'])->name('etapes.toggle');
        Route::put('etapes/{etape}/progress', [EtapeController::class, 'updateProgress'])->name('etapes.progress');
        Route::post('etapes/reorder', [EtapeController::class, 'reorder'])->name('etapes.reorder');
        Route::get('etapes/json', [EtapeController::class, 'getEtapes'])->name('etapes.json');
    });
    
    // Gestion des documents
    Route::prefix('chantiers/{chantier}')->group(function () {
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    });
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Gestion des commentaires
    Route::prefix('chantiers/{chantier}')->group(function () {
        Route::post('commentaires', [CommentaireController::class, 'store'])->name('commentaires.store');
    });
    Route::delete('commentaires/{commentaire}', [CommentaireController::class, 'destroy'])->name('commentaires.destroy');
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

    // ================================
    // NOUVELLES ROUTES POUR LE DASHBOARD AMÉLIORÉ
    // ================================
    
    // Routes pour les devis
    Route::prefix('devis')->group(function () {
        // Route pour traiter les demandes de devis
        Route::post('store', function (Illuminate\Http\Request $request) {
            $validated = $request->validate([
                'type_projet' => 'required|string',
                'budget_estime' => 'required|string',
                'description' => 'required|string',
                'date_debut_souhaitee' => 'nullable|date',
                'delai_prefere' => 'required|string',
            ]);

            // Si vous avez la table devis, décommentez cette partie :
            /*
            $devis = App\Models\Devis::create([
                'user_id' => auth()->id(),
                'type_projet' => $validated['type_projet'],
                'budget_estime' => $validated['budget_estime'],
                'description' => $validated['description'],
                'date_debut_souhaitee' => $validated['date_debut_souhaitee'],
                'delai_prefere' => $validated['delai_prefere'],
                'statut' => 'en_attente',
            ]);
            */

            // Créer une notification pour l'équipe commerciale
            $admins = App\Models\User::where('role', 'admin')->get();
            $commerciaux = App\Models\User::where('role', 'commercial')->get();

            foreach ($admins->concat($commerciaux) as $destinataire) {
                App\Models\Notification::create([
                    'user_id' => $destinataire->id,
                    'chantier_id' => null,
                    'type' => 'nouvelle_demande_devis',
                    'titre' => 'Nouvelle demande de devis',
                    'message' => "Demande de devis pour {$validated['type_projet']} de " . auth()->user()->name,
                ]);
            }

            // Envoyer un email de confirmation (optionnel)
            try {
                \Illuminate\Support\Facades\Mail::send('emails.confirmation-devis', [
                    'user' => auth()->user(),
                    'devis' => $validated
                ], function ($message) {
                    $message->to(auth()->user()->email)
                            ->subject('Confirmation de votre demande de devis');
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Erreur envoi email confirmation devis: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Votre demande de devis a été envoyée avec succès. Nous vous contacterons dans les 24h.'
            ]);
        })->name('devis.store');
        
        Route::get('nouveau', function () {
            return view('devis.nouveau');
        })->name('devis.nouveau');
        
        Route::get('mes-devis', function () {
            $user = auth()->user();
            
            if (!$user->isClient()) {
                abort(403);
            }
            
            // Si vous avez créé la table devis, utilisez ceci :
            // $devis = $user->devis()->orderBy('created_at', 'desc')->paginate(10);
            
            return view('devis.index', [
                'devis' => collect(), // Remplacer par $devis quand la table sera créée
            ]);
        })->name('devis.index');
    });
});

// Routes admin uniquement
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('dashboard', [AdminController::class, 'index'])->name('admin.dashboard'); // Alias
    
    // Gestion des utilisateurs
    Route::get('users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
    Route::post('users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::get('users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::put('users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::patch('users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('admin.users.toggle');
    
    // Actions en lot et export
    Route::post('users/bulk-action', [AdminController::class, 'bulkAction'])->name('admin.users.bulk-action');
    Route::get('users/export', [AdminController::class, 'exportUsers'])->name('admin.users.export');
    
    // Statistiques
    Route::get('statistics', [AdminController::class, 'statistics'])->name('admin.statistics');
    
    // Nettoyage des fichiers orphelins (admin seulement)
    Route::post('cleanup/files', [DocumentController::class, 'cleanupOrphanedFiles'])->name('admin.cleanup.files');
});

// ================================
// ROUTES API EXISTANTES (AJAX)
// ================================
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('chantiers/{chantier}/avancement', function (App\Models\Chantier $chantier) {
        // Vérification des autorisations
        if (!auth()->user()->can('view', $chantier)) {
            abort(403, 'Accès non autorisé');
        }
        
        return response()->json([
            'avancement' => $chantier->avancement_global,
            'etapes' => $chantier->etapes->map(function ($etape) {
                return [
                    'id' => $etape->id,
                    'nom' => $etape->nom,
                    'pourcentage' => $etape->pourcentage,
                    'terminee' => $etape->terminee,
                ];
            }),
        ]);
    })->name('api.chantiers.avancement');
    
    Route::get('notifications/count', function () {
        $count = auth()->user()->getNotificationsNonLues();
        return response()->json(['count' => $count]);
    })->name('api.notifications.count');

    // Route pour les mises à jour du dashboard
    Route::get('dashboard/progress', function () {
        $user = auth()->user();
        $updates = [];

        // Vérifier les nouvelles notifications
        $nouvelles_notifications = $user->notifications()
                                       ->where('created_at', '>', now()->subMinutes(5))
                                       ->where('lu', false)
                                       ->get();

        foreach ($nouvelles_notifications as $notification) {
            $updates[] = [
                'type' => 'success',
                'message' => $notification->titre . ': ' . $notification->message
            ];
        }

        return response()->json(['updates' => $updates]);
    })->name('api.dashboard.progress');
    
    // API pour les statistiques (admin seulement)
    Route::middleware(['role:admin'])->get('admin/stats', function () {
        return response()->json([
            'total_users' => \App\Models\User::count(),
            'total_chantiers' => \App\Models\Chantier::count(),
            'chantiers_actifs' => \App\Models\Chantier::where('statut', 'en_cours')->count(),
            'chantiers_termines' => \App\Models\Chantier::where('statut', 'termine')->count(),
            'chantiers_en_retard' => \App\Models\Chantier::whereDate('date_fin_prevue', '<', now())
                ->where('statut', '!=', 'termine')
                ->count(),
        ]);
    })->name('api.admin.stats');
    
    // API pour la recherche de chantiers
    Route::get('chantiers/search', function (Illuminate\Http\Request $request) {
        $query = $request->get('q', '');
        $user = auth()->user();
        
        $chantiersQuery = \App\Models\Chantier::query();
        
        // Filtrage selon le rôle
        if ($user->isCommercial()) {
            $chantiersQuery->where('commercial_id', $user->id);
        } elseif ($user->isClient()) {
            $chantiersQuery->where('client_id', $user->id);
        }
        
        $chantiers = $chantiersQuery->where(function ($q) use ($query) {
            $q->where('titre', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%");
        })
            ->with(['client', 'commercial'])
            ->limit(10)
            ->get()
            ->map(function ($chantier) {
                return [
                    'id' => $chantier->id,
                    'titre' => $chantier->titre,
                    'description' => $chantier->description,
                    'client' => $chantier->client->name,
                    'commercial' => $chantier->commercial->name,
                    'statut' => $chantier->statut,
                    'url' => route('chantiers.show', $chantier),
                ];
            });
        
        return response()->json($chantiers);
    })->name('api.chantiers.search');

    // API pour récupérer les détails d'un commercial
    Route::get('commercial/{user}', function (App\Models\User $user) {
        if ($user->role !== 'commercial') {
            abort(404);
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telephone' => $user->telephone,
            'specialites' => ['Cuisine', 'Salle de bain'], // À adapter selon vos besoins
            'note_moyenne' => 4.7, // À remplacer par de vraies données
            'projets_realises' => $user->chantiersCommercial()->where('statut', 'termine')->count(),
        ]);
    })->name('api.commercial.details');

    // API pour récupérer les documents d'un chantier
    Route::get('chantiers/{chantier}/documents', function (App\Models\Chantier $chantier) {
        if (!auth()->user()->can('view', $chantier)) {
            abort(403);
        }
        
        $documents = $chantier->documents->map(function ($document) {
            return [
                'id' => $document->id,
                'nom_original' => $document->nom_original,
                'type' => $document->type,
                'taille_formatee' => $document->getTailleFormatee(),
                'date_upload' => $document->created_at->format('d/m/Y'),
                'icone' => $document->getIconeType(),
                'url_download' => route('documents.download', $document),
            ];
        });
        
        return response()->json(['documents' => $documents]);
    })->name('api.chantiers.documents');

    // API pour noter un projet
    Route::post('chantiers/{chantier}/notation', function (App\Models\Chantier $chantier, Illuminate\Http\Request $request) {
        if (!auth()->user()->can('view', $chantier) || $chantier->statut !== 'termine') {
            abort(403);
        }
        
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:1000',
        ]);
        
        // Si vous avez créé la table ratings, décommentez ceci :
        /*
        App\Models\Rating::create([
            'chantier_id' => $chantier->id,
            'user_id' => auth()->id(),
            'note_globale' => $validated['rating'],
            'note_qualite' => $validated['rating'],
            'note_delais' => $validated['rating'],
            'note_communication' => $validated['rating'],
            'commentaire' => $validated['commentaire'],
        ]);
        */
        
        // Créer une notification pour le commercial
        App\Models\Notification::create([
            'user_id' => $chantier->commercial_id,
            'chantier_id' => $chantier->id,
            'type' => 'nouvelle_notation',
            'titre' => 'Nouvelle évaluation client',
            'message' => auth()->user()->name . " a évalué le chantier '{$chantier->titre}' avec {$validated['rating']} étoiles",
        ]);
        
        return response()->json(['success' => true, 'message' => 'Évaluation enregistrée avec succès']);
    })->name('api.chantiers.notation');

    // API pour demander un rappel
    Route::post('rappel/demander', function (Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'commercial_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
        ]);
        
        $commercial = App\Models\User::find($validated['commercial_id']);
        
        // Créer une notification pour le commercial
        App\Models\Notification::create([
            'user_id' => $commercial->id,
            'chantier_id' => null,
            'type' => 'demande_rappel',
            'titre' => 'Demande de rappel',
            'message' => auth()->user()->name . ' demande à être rappelé. ' . ($validated['message'] ?? ''),
        ]);
        
        return response()->json(['success' => true]);
    })->name('api.rappel.demander');
});

// ================================
// NOUVELLES ROUTES API DASHBOARD OPTIMISÉ
// ================================
Route::prefix('api/v2')->middleware(['auth', 'verified'])->group(function () {
    
    // ===== PHOTOS =====
    Route::prefix('photos')->group(function () {
        Route::get('/all', [ApiPhotoController::class, 'getAllUserPhotos'])->name('api.v2.photos.all');
        Route::get('/{photo}', [ApiPhotoController::class, 'show'])->name('api.v2.photos.show');
        Route::get('/{photo}/download', [ApiPhotoController::class, 'download'])->name('api.v2.photos.download');
        Route::post('/upload', [ApiPhotoController::class, 'upload'])->name('api.v2.photos.upload');
        Route::put('/{photo}', [ApiPhotoController::class, 'update'])->name('api.v2.photos.update');
        Route::delete('/{photo}', [ApiPhotoController::class, 'destroy'])->name('api.v2.photos.destroy');
        Route::get('/search', [ApiPhotoController::class, 'search'])->name('api.v2.photos.search');
    });
    
    // ===== CHANTIERS =====
    Route::prefix('chantiers')->group(function () {
        Route::get('/{chantier}/photos', [ApiPhotoController::class, 'getChantierPhotos'])->name('api.v2.chantiers.photos');
        Route::get('/{chantier}/stats', [ApiDashboardController::class, 'getChantierStats'])->name('api.v2.chantiers.stats');
        Route::get('/{chantier}/etapes', [ApiDashboardController::class, 'getChantierEtapes'])->name('api.v2.chantiers.etapes');
        Route::get('/{chantier}/documents', [ApiDashboardController::class, 'getChantierDocuments'])->name('api.v2.chantiers.documents');
    });
    
    // ===== DASHBOARD =====
    Route::prefix('dashboard')->group(function () {
        Route::get('/refresh', [ApiDashboardController::class, 'refresh'])->name('api.v2.dashboard.refresh');
        Route::get('/stats', [ApiDashboardController::class, 'getStats'])->name('api.v2.dashboard.stats');
        Route::get('/activity', [ApiDashboardController::class, 'getRecentActivity'])->name('api.v2.dashboard.activity');
        Route::get('/projects/active', [ApiDashboardController::class, 'getActiveProjects'])->name('api.v2.dashboard.projects.active');
        Route::get('/progress', [ApiDashboardController::class, 'getGlobalProgress'])->name('api.v2.dashboard.progress');
    });
    
    // ===== DEVIS =====
    Route::prefix('devis')->group(function () {
        Route::post('/', [ApiDevisController::class, 'store'])->name('api.v2.devis.store');
        Route::get('/', [ApiDevisController::class, 'index'])->name('api.v2.devis.index');
        Route::get('/{devis}', [ApiDevisController::class, 'show'])->name('api.v2.devis.show');
    });
    
    // ===== NOTIFICATIONS =====
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('{notification}/view', [NotificationController::class, 'viewAndMarkAsRead'])->name('notifications.view');
        Route::post('{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });
    
    // ===== ÉVALUATIONS =====
    Route::prefix('evaluations')->group(function () {
        Route::post('/', [ApiEvaluationController::class, 'store'])->name('api.v2.evaluations.store');
        Route::get('/', [ApiEvaluationController::class, 'index'])->name('api.v2.evaluations.index');
        Route::put('/{evaluation}', [ApiEvaluationController::class, 'update'])->name('api.v2.evaluations.update');
        Route::get('/types', [ApiEvaluationController::class, 'getTypes'])->name('api.v2.evaluations.types');
        Route::get('/stats', [ApiEvaluationController::class, 'getGlobalStats'])->name('api.v2.evaluations.stats');
    });
    
    // ===== RECHERCHE =====
    Route::get('/search', [ApiDashboardController::class, 'globalSearch'])->name('api.v2.search.global');
    
    // ===== COMMUNICATION =====
    Route::post('/communication/message', [ApiDashboardController::class, 'sendMessage'])->name('api.v2.communication.message');
});

// Route publique pour les types de projets
Route::get('/api/v2/project-types', [ApiDevisController::class, 'getProjectTypes'])->name('api.v2.project-types');

// Route de test pour vérifier la nouvelle API
Route::get('/api/v2/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '2.0',
        'timestamp' => now(),
        'message' => 'API Dashboard Client v2 fonctionnelle'
    ]);
})->name('api.v2.health');

// Routes d'erreur personnalisées
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Route non trouvée'], 404);
    }
    return response()->view('errors.404', [], 404);
});

// Routes de test (à supprimer en production)
if (app()->environment('local')) {
    Route::get('/test-email', function () {
        return view('emails.notification', [
            'notification' => \App\Models\Notification::first() ?? new \App\Models\Notification(),
            'user' => \App\Models\User::first() ?? new \App\Models\User(),
            'chantier' => \App\Models\Chantier::first() ?? new \App\Models\Chantier(),
        ]);
    });
}

// Ajouter ce bloc de routes à la fin des routes existantes (avant la dernière accolade })
// Routes pour les messages
Route::middleware(['auth'])->prefix('messages')->group(function () {
    Route::get('/', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/sent', [MessageController::class, 'sent'])->name('messages.sent');
    Route::get('/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/{message}', [MessageController::class, 'show'])->name('messages.show');
    Route::get('/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    Route::get('/modal', [MessageController::class, 'modal'])->name('messages.modal');
});

// API pour le compteur de messages non lus
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('messages/unread-count', function () {
        return response()->json([
            'count' => Auth::user()->getUnreadMessagesCount()
        ]);
    })->name('api.messages.unread-count');
});