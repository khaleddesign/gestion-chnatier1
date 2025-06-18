<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class EvaluationController extends Controller
{
    /**
     * Créer une nouvelle évaluation
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'note' => 'required|integer|min:1|max:5',
            'type' => 'required|string|in:experience_globale,projet_specifique,commercial,qualite_travaux,respect_delais,communication',
            'chantier_id' => 'sometimes|exists:chantiers,id',
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            // TODO: Créer le modèle Evaluation quand la table sera prête
            /*
            \App\Models\Evaluation::create([
                'user_id' => Auth::id(),
                'chantier_id' => $request->chantier_id,
                'type' => $request->type,
                'note' => $request->note,
                'commentaire' => $request->commentaire,
            ]);
            */

            // Créer une notification si la note est faible
            if ($request->note <= 2) {
                $admins = \App\Models\User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'titre' => 'Évaluation client faible',
                        'message' => Auth::user()->name . " a donné une note de {$request->note}/5 pour {$request->type}",
                        'type' => 'evaluation_faible',
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Évaluation enregistrée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de l\'évaluation'
            ], 500);
        }
    }

    /**
     * Obtenir les évaluations de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'evaluations' => [],
            'stats' => [
                'note_moyenne' => 0,
                'total_evaluations' => 0
            ],
            'message' => 'Fonctionnalité en développement - Table evaluations à créer'
        ]);
    }

    /**
     * Mettre à jour une évaluation
     */
    public function update(Request $request, $evaluation): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Fonctionnalité en développement'
        ]);
    }

    /**
     * Obtenir les types d'évaluations disponibles
     */
    public function getTypes(): JsonResponse
    {
        $types = [
            'experience_globale' => [
                'label' => 'Expérience globale',
                'description' => 'Votre satisfaction générale avec nos services',
                'icon' => 'fas fa-star',
            ],
            'projet_specifique' => [
                'label' => 'Projet spécifique',
                'description' => 'Évaluation d\'un projet en particulier',
                'icon' => 'fas fa-project-diagram',
            ],
            'commercial' => [
                'label' => 'Commercial',
                'description' => 'Qualité du suivi commercial et conseil',
                'icon' => 'fas fa-user-tie',
            ],
            'qualite_travaux' => [
                'label' => 'Qualité des travaux',
                'description' => 'Satisfaction concernant la qualité des travaux réalisés',
                'icon' => 'fas fa-hammer',
            ],
            'respect_delais' => [
                'label' => 'Respect des délais',
                'description' => 'Respect des délais annoncés pour vos projets',
                'icon' => 'fas fa-clock',
            ],
            'communication' => [
                'label' => 'Communication',
                'description' => 'Qualité de la communication tout au long du projet',
                'icon' => 'fas fa-comments',
            ],
        ];

        return response()->json([
            'success' => true,
            'types' => $types
        ]);
    }

    /**
     * Obtenir les statistiques globales des évaluations
     */
    public function getGlobalStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'stats_by_type' => [],
            'evolution' => [],
            'global_average' => 0,
            'total_evaluations' => 0,
            'message' => 'Fonctionnalité en développement'
        ]);
    }
}