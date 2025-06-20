<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chantier;
use App\Models\Notification;
use App\Http\Controllers\Controller;

class ApiDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirection selon le rôle
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'commercial') {
            return $this->commercialDashboard();
        }

        if ($user->role === 'client') {
            return $this->clientDashboard();
        }

        // Fallback par défaut
        return $this->clientDashboard();
    }

    private function clientDashboard()
    {
        $user = Auth::user();

        // Charger les chantiers directement via le modèle Chantier (sans relation User)
        $mes_chantiers = Chantier::where('client_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Initialiser manuellement les relations pour éviter les erreurs
        foreach ($mes_chantiers as $chantier) {
            // Initialiser les relations avec des collections vides si elles n'existent pas
            try {
                // Tenter de charger les étapes
                if (!$chantier->relationLoaded('etapes')) {
                    $etapes = $chantier->etapes;
                    if (!$etapes) {
                        $chantier->setRelation('etapes', collect());
                    }
                }
            } catch (\Exception $e) {
                $chantier->setRelation('etapes', collect());
            }

            try {
                // Tenter de charger les photos
                if (!$chantier->relationLoaded('photos')) {
                    $photos = $chantier->photos;
                    if (!$photos) {
                        $chantier->setRelation('photos', collect());
                    }
                }
            } catch (\Exception $e) {
                $chantier->setRelation('photos', collect());
            }

            try {
                // Tenter de charger les documents
                if (!$chantier->relationLoaded('documents')) {
                    $documents = $chantier->documents;
                    if (!$documents) {
                        $chantier->setRelation('documents', collect());
                    }
                }
            } catch (\Exception $e) {
                $chantier->setRelation('documents', collect());
            }

            try {
                // Tenter de charger le commercial
                if (!$chantier->relationLoaded('commercial')) {
                    $commercial = $chantier->commercial;
                }
            } catch (\Exception $e) {
                // Le commercial peut être null, c'est normal
            }
        }

        // Charger les notifications directement via le modèle Notification
        try {
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } catch (\Exception $e) {
            $notifications = collect();
        }

        // S'assurer que notifications n'est pas null
        if (!$notifications) {
            $notifications = collect();
        }

        return view('dashboard.client', compact('mes_chantiers', 'notifications'));
    }

    private function commercialDashboard()
    {
        $user = Auth::user();

        // Statistiques pour le commercial via requête directe
        try {
            $mes_chantiers = Chantier::where('commercial_id', $user->id)
                ->with(['client'])
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            $mes_chantiers = collect();
        }

        // Initialiser les relations manquantes
        foreach ($mes_chantiers as $chantier) {
            try {
                if (!$chantier->relationLoaded('etapes')) {
                    $etapes = $chantier->etapes;
                    if (!$etapes) {
                        $chantier->setRelation('etapes', collect());
                    }
                }
            } catch (\Exception $e) {
                $chantier->setRelation('etapes', collect());
            }

            try {
                if (!$chantier->relationLoaded('documents')) {
                    $documents = $chantier->documents;
                    if (!$documents) {
                        $chantier->setRelation('documents', collect());
                    }
                }
            } catch (\Exception $e) {
                $chantier->setRelation('documents', collect());
            }
        }

        $stats = [
            'total_chantiers' => $mes_chantiers->count(),
            'en_cours' => $mes_chantiers->where('statut', 'en_cours')->count(),
            'termines' => $mes_chantiers->where('statut', 'termine')->count(),
            'avancement_moyen' => $mes_chantiers->count() > 0 ? $mes_chantiers->avg('avancement_global') ?: 0 : 0,
        ];

        try {
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $notifications = collect();
        }

        if (!$notifications) {
            $notifications = collect();
        }

        return view('dashboard.commercial', compact('mes_chantiers', 'stats', 'notifications'));
    }

    /**
     * Méthodes API pour les statistiques de chantier
     */
    public function getChantierStats($chantierId)
    {
        try {
            $chantier = Chantier::findOrFail($chantierId);
            
            // Vérifier l'accès
            $user = Auth::user();
            if (!$user->isAdmin() && $chantier->client_id !== $user->id && $chantier->commercial_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            return response()->json([
                'success' => true,
                'stats' => [
                    'avancement' => $chantier->avancement_global ?? 0,
                    'photos_count' => $chantier->photos()->count(),
                    'documents_count' => $chantier->documents()->count(),
                    'etapes_count' => $chantier->etapes()->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getChantierEtapes($chantierId)
    {
        try {
            $chantier = Chantier::findOrFail($chantierId);
            
            // Vérifier l'accès
            $user = Auth::user();
            if (!$user->isAdmin() && $chantier->client_id !== $user->id && $chantier->commercial_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            $etapes = $chantier->etapes()->orderBy('ordre')->get();

            return response()->json([
                'success' => true,
                'etapes' => $etapes
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getChantierDocuments($chantierId)
    {
        try {
            $chantier = Chantier::findOrFail($chantierId);
            
            // Vérifier l'accès
            $user = Auth::user();
            if (!$user->isAdmin() && $chantier->client_id !== $user->id && $chantier->commercial_id !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
            }

            $documents = $chantier->documents()->latest()->get();

            return response()->json([
                'success' => true,
                'documents' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function refresh()
    {
        return response()->json(['success' => true, 'message' => 'Dashboard actualisé']);
    }

    public function getStats()
    {
        $user = Auth::user();
        
        try {
            if ($user->isClient()) {
                $chantiers = Chantier::where('client_id', $user->id)->get();
            } elseif ($user->isCommercial()) {
                $chantiers = Chantier::where('commercial_id', $user->id)->get();
            } else {
                $chantiers = Chantier::all();
            }

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_chantiers' => $chantiers->count(),
                    'en_cours' => $chantiers->where('statut', 'en_cours')->count(),
                    'termines' => $chantiers->where('statut', 'termine')->count(),
                    'avancement_moyen' => $chantiers->avg('avancement_global') ?? 0,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRecentActivity()
    {
        $user = Auth::user();
        
        try {
            $notifications = Notification::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'success' => true,
                'activities' => $notifications
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getActiveProjects()
    {
        $user = Auth::user();
        
        try {
            if ($user->isClient()) {
                $projets = Chantier::where('client_id', $user->id)
                    ->where('statut', 'en_cours')
                    ->get();
            } elseif ($user->isCommercial()) {
                $projets = Chantier::where('commercial_id', $user->id)
                    ->where('statut', 'en_cours')
                    ->get();
            } else {
                $projets = Chantier::where('statut', 'en_cours')->get();
            }

            return response()->json([
                'success' => true,
                'projects' => $projets
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getGlobalProgress()
    {
        $user = Auth::user();
        
        try {
            if ($user->isClient()) {
                $chantiers = Chantier::where('client_id', $user->id)->get();
            } elseif ($user->isCommercial()) {
                $chantiers = Chantier::where('commercial_id', $user->id)->get();
            } else {
                $chantiers = Chantier::all();
            }

            $progress = $chantiers->avg('avancement_global') ?? 0;

            return response()->json([
                'success' => true,
                'progress' => round($progress, 2)
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function globalSearch(Request $request)
    {
        $query = $request->get('q');
        $user = Auth::user();
        
        try {
            $results = [];
            
            // Recherche dans les chantiers
            $chantiersQuery = Chantier::where('titre', 'LIKE', "%{$query}%");
            
            if ($user->isClient()) {
                $chantiersQuery->where('client_id', $user->id);
            } elseif ($user->isCommercial()) {
                $chantiersQuery->where('commercial_id', $user->id);
            }
            
            $chantiers = $chantiersQuery->get();
            
            foreach ($chantiers as $chantier) {
                $results[] = [
                    'type' => 'chantier',
                    'id' => $chantier->id,
                    'title' => $chantier->titre,
                    'url' => route('chantiers.show', $chantier)
                ];
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'query' => $query
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        // Cette méthode sera implémentée selon vos besoins
        return response()->json([
            'success' => true,
            'message' => 'Message envoyé'
        ]);
    }
}


Route::middleware('auth:sanctum')->group(function () {
    // API Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'getUnreadCount']);
        Route::get('/', [App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/{notification}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::post('/mark-all-read', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    });
    
    // API Messages
    Route::prefix('messages')->group(function () {
        Route::get('/unread-count', function() {
            return response()->json(['count' => Auth::user()->getUnreadMessagesCount()]);
        });
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
    });
});