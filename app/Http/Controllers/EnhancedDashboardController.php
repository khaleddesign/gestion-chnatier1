<?php

namespace App\Http\Controllers;

use App\Models\Chantier;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EnhancedDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        switch ($user->role) {
            case 'admin':
                return $this->dashboardAdmin();
            case 'commercial':
                return $this->dashboardCommercial();
            case 'client':
                return $this->dashboardClientEnhanced();
            default:
                abort(403);
        }
    }

    // Tableau de bord CLIENT AMÉLIORÉ
    private function dashboardClientEnhanced()
    {
        $user = Auth::user();

        // Récupérer les chantiers avec toutes les relations nécessaires
        $mes_chantiers = $user->chantiersClient()
                             ->with([
                                 'commercial', 
                                 'etapes' => function($query) {
                                     $query->orderBy('ordre');
                                 },
                                 'documents' => function($query) {
                                     $query->orderBy('created_at', 'desc');
                                 },
                                 'commentaires' => function($query) {
                                     $query->with('user')->latest();
                                 }
                             ])
                             ->orderBy('created_at', 'desc')
                             ->get();

        // Récupérer les notifications avec pagination
        $notifications = $user->notifications()
                             ->with('chantier')
                             ->orderBy('created_at', 'desc')
                             ->get();

        // Statistiques avancées
        $stats = [
            'total_chantiers' => $mes_chantiers->count(),
            'en_cours' => $mes_chantiers->where('statut', 'en_cours')->count(),
            'termines' => $mes_chantiers->where('statut', 'termine')->count(),
            'planifies' => $mes_chantiers->where('statut', 'planifie')->count(),
            'en_retard' => $mes_chantiers->filter(function($chantier) {
                return $chantier->isEnRetard();
            })->count(),
            'avancement_moyen' => round($mes_chantiers->avg('avancement_global') ?? 0, 1),
            'budget_total' => $mes_chantiers->sum('budget'),
            'prochaine_echeance' => $this->getProchaineDateImportante($mes_chantiers),
            'satisfaction_moyenne' => $this->calculerSatisfactionMoyenne($user),
        ];

        // Activités récentes (combinant notifications et événements chantiers)
        $activites_recentes = $this->getActivitesRecentes($user, $mes_chantiers);

        // Recommandations personnalisées
        $recommandations = $this->genererRecommandations($user, $mes_chantiers);

        return view('dashboard.client-enhanced', compact(
            'mes_chantiers', 
            'notifications', 
            'stats', 
            'activites_recentes',
            'recommandations'
        ));
    }

    // Tableau de bord ADMIN (optimisé)
    private function dashboardAdmin()
    {
        $chantiers = Chantier::with(['client', 'commercial'])->orderBy('created_at', 'desc')->get();

        $stats = [
            'total_chantiers' => $chantiers->count(),
            'chantiers_en_cours' => $chantiers->where('statut', 'en_cours')->count(),
            'chantiers_termines' => $chantiers->where('statut', 'termine')->count(),
            'chantiers_planifies' => $chantiers->where('statut', 'planifie')->count(),
            'total_clients' => User::where('role', 'client')->count(),
            'total_commerciaux' => User::where('role', 'commercial')->count(),
            'avancement_moyen' => $chantiers->avg('avancement_global') ?? 0,
            'chiffre_affaires' => $chantiers->where('statut', 'termine')->sum('budget'),
            'chantiers_en_retard' => $chantiers->filter(function($chantier) {
                return $chantier->isEnRetard();
            })->count(),
        ];

        $chantiers_recents = $chantiers->take(10);

        $chantiers_retard = $chantiers->filter(function($chantier) {
            return $chantier->isEnRetard();
        });

        // Données pour les graphiques
        $donnees_graphiques = $this->genererDonneesGraphiques();

        return view('dashboard.admin', compact(
            'stats', 
            'chantiers_recents', 
            'chantiers_retard', 
            'chantiers',
            'donnees_graphiques'
        ));
    }

    // Tableau de bord COMMERCIAL (optimisé)
    private function dashboardCommercial()
    {
        $user = Auth::user();

        $mes_chantiers = Chantier::where('commercial_id', $user->id)
                                 ->with(['client', 'etapes', 'documents'])
                                 ->orderBy('created_at', 'desc')
                                 ->get();

        $stats = [
            'total_chantiers' => $mes_chantiers->count(),
            'en_cours' => $mes_chantiers->where('statut', 'en_cours')->count(),
            'termines' => $mes_chantiers->where('statut', 'termine')->count(),
            'planifies' => $mes_chantiers->where('statut', 'planifie')->count(),
            'avancement_moyen' => $mes_chantiers->avg('avancement_global') ?? 0,
            'chiffre_affaires' => $mes_chantiers->where('statut', 'termine')->sum('budget'),
            'objectif_mensuel' => 150000, // À récupérer depuis les paramètres utilisateur
            'taux_conversion' => $this->calculerTauxConversion($user),
        ];

        $notifications = $user->notifications()
                             ->where('lu', false)
                             ->orderBy('created_at', 'desc')
                             ->limit(5)
                             ->get();

        // Tâches à effectuer
        $taches = $this->genererTachesCommercial($mes_chantiers);

        return view('dashboard.commercial', compact(
            'mes_chantiers', 
            'stats', 
            'notifications',
            'taches'
        ));
    }

    // MÉTHODES UTILITAIRES

    private function getProchaineDateImportante($chantiers)
    {
        $dates_importantes = [];

        foreach ($chantiers as $chantier) {
            if ($chantier->date_fin_prevue && $chantier->statut !== 'termine') {
                $dates_importantes[] = [
                    'date' => $chantier->date_fin_prevue,
                    'type' => 'fin_prevue',
                    'chantier' => $chantier->titre
                ];
            }

            foreach ($chantier->etapes as $etape) {
                if ($etape->date_fin_prevue && !$etape->terminee) {
                    $dates_importantes[] = [
                        'date' => $etape->date_fin_prevue,
                        'type' => 'etape',
                        'chantier' => $chantier->titre,
                        'etape' => $etape->nom
                    ];
                }
            }
        }

        // Trier par date et prendre la plus proche
        usort($dates_importantes, function($a, $b) {
            return $a['date']->timestamp - $b['date']->timestamp;
        });

        return $dates_importantes[0] ?? null;
    }

    private function calculerSatisfactionMoyenne($user)
    {
        // Simulation - à remplacer par de vraies données de satisfaction
        return 4.2;
    }

    private function getActivitesRecentes($user, $chantiers)
    {
        $activites = collect();

        // Ajouter les notifications récentes
        $notifications = $user->notifications()
                             ->latest()
                             ->take(5)
                             ->get();

        foreach ($notifications as $notification) {
            $activites->push([
                'type' => 'notification',
                'titre' => $notification->titre,
                'description' => $notification->message,
                'date' => $notification->created_at,
                'icone' => 'fas fa-bell',
                'couleur' => 'blue'
            ]);
        }

        // Ajouter les dernières modifications d'étapes
        foreach ($chantiers as $chantier) {
            $etapes_recentes = $chantier->etapes()
                                      ->where('updated_at', '>=', now()->subDays(7))
                                      ->orderBy('updated_at', 'desc')
                                      ->get();

            foreach ($etapes_recentes as $etape) {
                $activites->push([
                    'type' => 'etape',
                    'titre' => 'Étape mise à jour',
                    'description' => "{$etape->nom} - {$chantier->titre}",
                    'date' => $etape->updated_at,
                    'icone' => $etape->terminee ? 'fas fa-check-circle' : 'fas fa-tools',
                    'couleur' => $etape->terminee ? 'green' : 'orange'
                ]);
            }
        }

        // Trier par date et prendre les 10 plus récents
        return $activites->sortByDesc('date')->take(10)->values();
    }

    private function genererRecommandations($user, $chantiers)
    {
        $recommandations = collect();

        // Recommandation basée sur les projets terminés
        $projets_termines = $chantiers->where('statut', 'termine');
        if ($projets_termines->count() > 0) {
            $types_projets = $projets_termines->pluck('titre')->map(function($titre) {
                if (str_contains(strtolower($titre), 'cuisine')) return 'cuisine';
                if (str_contains(strtolower($titre), 'salle de bain')) return 'salle_bain';
                if (str_contains(strtolower($titre), 'extension')) return 'extension';
                return 'autre';
            })->countBy();

            $type_favori = $types_projets->keys()->first();

            if ($type_favori === 'cuisine') {
                $recommandations->push([
                    'titre' => 'Optimisez votre cuisine',
                    'description' => 'Découvrez nos nouvelles solutions d\'îlots modulables',
                    'icone' => 'fas fa-kitchen-set',
                    'action' => 'Voir les options',
                    'url' => '#'
                ]);
            }
        }

        // Recommandation basée sur la saison
        $mois_actuel = now()->month;
        if (in_array($mois_actuel, [3, 4, 5])) { // Printemps
            $recommandations->push([
                'titre' => 'Projets de printemps',
                'description' => 'C\'est le moment idéal pour les projets d\'extension',
                'icone' => 'fas fa-seedling',
                'action' => 'Demander un devis',
                'url' => '#'
            ]);
        }

        // Recommandation maintenance
        $chantiers_anciens = $chantiers->where('statut', 'termine')
                                     ->filter(function($chantier) {
                                         return $chantier->updated_at->lt(now()->subYear());
                                     });

        if ($chantiers_anciens->count() > 0) {
            $recommandations->push([
                'titre' => 'Maintenance préventive',
                'description' => 'Il est temps de vérifier vos anciens projets',
                'icone' => 'fas fa-tools',
                'action' => 'Planifier une visite',
                'url' => '#'
            ]);
        }

        return $recommandations->take(3);
    }

    private function calculerTauxConversion($commercial)
    {
        // Simulation - à remplacer par la vraie logique
        $total_devis = 45; // Nombre de devis envoyés
        $devis_convertis = 12; // Nombre de devis convertis en chantiers
        
        return $total_devis > 0 ? round(($devis_convertis / $total_devis) * 100, 1) : 0;
    }

    private function genererTachesCommercial($chantiers)
    {
        $taches = collect();

        // Chantiers nécessitant un suivi
        foreach ($chantiers as $chantier) {
            if ($chantier->statut === 'en_cours' && $chantier->updated_at->lt(now()->subDays(3))) {
                $taches->push([
                    'type' => 'suivi',
                    'titre' => 'Faire un point sur ' . $chantier->titre,
                    'priorite' => 'medium',
                    'url' => route('chantiers.show', $chantier)
                ]);
            }

            if ($chantier->isEnRetard()) {
                $taches->push([
                    'type' => 'urgence',
                    'titre' => 'Chantier en retard : ' . $chantier->titre,
                    'priorite' => 'high',
                    'url' => route('chantiers.show', $chantier)
                ]);
            }
        }

        return $taches->take(5);
    }

    private function genererDonneesGraphiques()
    {
        // Données pour les graphiques du dashboard admin
        return [
            'evolution_chantiers' => $this->getEvolutionChantiers(),
            'repartition_statuts' => $this->getRepartitionStatuts(),
            'performance_commerciaux' => $this->getPerformanceCommerciaux(),
        ];
    }

    private function getEvolutionChantiers()
    {
        // Évolution du nombre de chantiers sur les 12 derniers mois
        $donnees = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Chantier::whereYear('created_at', $date->year)
                            ->whereMonth('created_at', $date->month)
                            ->count();
            
            $donnees[] = [
                'mois' => $date->format('M Y'),
                'nombre' => $count
            ];
        }
        
        return $donnees;
    }

    private function getRepartitionStatuts()
    {
        return [
            'planifies' => Chantier::where('statut', 'planifie')->count(),
            'en_cours' => Chantier::where('statut', 'en_cours')->count(),
            'termines' => Chantier::where('statut', 'termine')->count(),
        ];
    }

    private function getPerformanceCommerciaux()
    {
        return User::where('role', 'commercial')
                  ->withCount(['chantiersCommercial as total_chantiers'])
                  ->get()
                  ->map(function($commercial) {
                      return [
                          'nom' => $commercial->name,
                          'total_chantiers' => $commercial->total_chantiers,
                          'chiffre_affaires' => $commercial->chantiersCommercial()
                                                          ->where('statut', 'termine')
                                                          ->sum('budget')
                      ];
                  })
                  ->sortByDesc('total_chantiers')
                  ->values();
    }

    // API pour les mises à jour en temps réel
    public function apiProgress()
    {
        $user = Auth::user();
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

        // Vérifier les mises à jour d'avancement
        if ($user->isClient()) {
            $chantiers_mis_a_jour = $user->chantiersClient()
                                        ->whereHas('etapes', function($query) {
                                            $query->where('updated_at', '>', now()->subMinutes(5));
                                        })
                                        ->with('etapes')
                                        ->get();

            foreach ($chantiers_mis_a_jour as $chantier) {
                $etapes_recentes = $chantier->etapes()
                                           ->where('updated_at', '>', now()->subMinutes(5))
                                           ->get();

                foreach ($etapes_recentes as $etape) {
                    if ($etape->terminee) {
                        $updates[] = [
                            'type' => 'success',
                            'message' => "Étape '{$etape->nom}' terminée sur {$chantier->titre}"
                        ];
                    }
                }
            }
        }

        return response()->json(['updates' => $updates]);
    }

    // Gestion des demandes de devis
    public function storeDevis(Request $request)
    {
        $validated = $request->validate([
            'type_projet' => 'required|string',
            'budget_estime' => 'required|string',
            'description' => 'required|string',
            'date_debut_souhaitee' => 'nullable|date',
            'delai_prefere' => 'required|string',
        ]);

        // Créer une notification pour l'équipe commerciale
        $admins = User::where('role', 'admin')->get();
        $commerciaux = User::where('role', 'commercial')->get();

        foreach ($admins->concat($commerciaux) as $destinataire) {
            Notification::create([
                'user_id' => $destinataire->id,
                'chantier_id' => null,
                'type' => 'nouvelle_demande_devis',
                'titre' => 'Nouvelle demande de devis',
                'message' => "Demande de devis pour {$validated['type_projet']} de " . Auth::user()->name,
            ]);
        }

        // Envoyer un email de confirmation au client
        try {
            Mail::send('emails.confirmation-devis', [
                'user' => Auth::user(),
                'devis' => $validated
            ], function ($message) {
                $message->to(Auth::user()->email)
                        ->subject('Confirmation de votre demande de devis');
            });
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email confirmation devis: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Votre demande de devis a été envoyée avec succès. Nous vous contacterons dans les 24h.'
        ]);
    }
}