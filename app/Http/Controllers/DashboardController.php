<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chantier;
use App\Models\Notification;

class DashboardController extends Controller
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

        // Charger les chantiers de base sans les relations problématiques
        $mes_chantiers = $user->chantiers()
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

        // Charger les notifications de manière sécurisée
        try {
            $notifications = $user->notifications()
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

        // Statistiques pour le commercial
        try {
            $mes_chantiers = $user->chantiersCommercial()
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
            $notifications = $user->notifications()
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
}