<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class DevisController extends Controller
{
    /**
     * Créer une nouvelle demande de devis
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type_projet' => 'required|string|max:100',
            'budget_estime' => 'required|string|max:50',
            'description' => 'required|string|max:2000',
            'date_debut_souhaitee' => 'nullable|date|after:today',
            'delai_prefere' => 'required|string|max:50',
        ]);

        try {
            // TODO: Créer le modèle Devis quand la table sera prête
            // Pour l'instant, on crée juste des notifications
            
            // Créer une notification pour l'équipe commerciale
            $commerciaux = \App\Models\User::where('role', 'commercial')->get();
            $admins = \App\Models\User::where('role', 'admin')->get();

            foreach ($commerciaux->concat($admins) as $destinataire) {
                \App\Models\Notification::create([
                    'user_id' => $destinataire->id,
                    'titre' => 'Nouvelle demande de devis',
                    'message' => "Demande de devis pour {$request->type_projet} de " . Auth::user()->name,
                    'type' => 'nouveau_devis',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Demande de devis envoyée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de la demande'
            ], 500);
        }
    }

    /**
     * Obtenir la liste des devis de l'utilisateur
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'devis' => [],
            'count' => 0,
            'message' => 'Fonctionnalité en développement - Table devis à créer'
        ]);
    }

    /**
     * Afficher un devis spécifique
     */
    public function show($devis): JsonResponse
    {
        return response()->json([
            'success' => true,
            'devis' => null,
            'message' => 'Fonctionnalité en développement'
        ]);
    }

    /**
     * Obtenir les types de projets disponibles
     */
    public function getProjectTypes(): JsonResponse
    {
        $types = [
            'cuisine' => [
                'label' => 'Cuisine',
                'description' => 'Rénovation ou installation de cuisine',
                'budget_moyen' => '15000-50000€',
                'duree_moyenne' => '2-6 semaines',
            ],
            'salle_bain' => [
                'label' => 'Salle de bain',
                'description' => 'Rénovation ou installation de salle de bain',
                'budget_moyen' => '8000-25000€',
                'duree_moyenne' => '1-3 semaines',
            ],
            'extension' => [
                'label' => 'Extension',
                'description' => 'Extension de maison ou agrandissement',
                'budget_moyen' => '25000-100000€',
                'duree_moyenne' => '2-6 mois',
            ],
            'renovation' => [
                'label' => 'Rénovation complète',
                'description' => 'Rénovation complète d\'un logement',
                'budget_moyen' => '50000-200000€',
                'duree_moyenne' => '3-12 mois',
            ],
            'amenagement' => [
                'label' => 'Aménagement',
                'description' => 'Aménagement d\'espaces (combles, sous-sol...)',
                'budget_moyen' => '10000-40000€',
                'duree_moyenne' => '2-8 semaines',
            ],
            'autre' => [
                'label' => 'Autre',
                'description' => 'Autre type de projet',
                'budget_moyen' => 'Variable',
                'duree_moyenne' => 'Variable',
            ],
        ];

        return response()->json([
            'success' => true,
            'types' => $types
        ]);
    }
}