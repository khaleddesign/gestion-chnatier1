<?php
namespace Database\Factories;

use App\Models\{Notification, User, Chantier};
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition()
    {
        $types = [
            'nouveau_chantier',
            'changement_statut',
            'nouvelle_etape',
            'etape_terminee',
            'nouveau_document',
            'nouveau_commentaire_client',
            'nouveau_commentaire_commercial',
            'chantier_retard',
            'nouveau_message',
            'compte_cree',
        ];

        $type = $this->faker->randomElement($types);
        
        return [
            'user_id' => User::factory(),
            'chantier_id' => $this->faker->boolean(80) ? Chantier::factory() : null,
            'type' => $type,
            'titre' => $this->generateTitle($type),
            'message' => $this->generateMessage($type),
            'lu' => $this->faker->boolean(40),
            'lu_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }

    private function generateTitle($type)
    {
        return match($type) {
            'nouveau_chantier' => 'Nouveau chantier créé',
            'changement_statut' => 'Statut du chantier modifié',
            'nouvelle_etape' => 'Nouvelle étape ajoutée',
            'etape_terminee' => 'Étape terminée',
            'nouveau_document' => 'Nouveau document ajouté',
            'nouveau_commentaire_client' => 'Nouveau commentaire client',
            'nouveau_commentaire_commercial' => 'Réponse du commercial',
            'chantier_retard' => 'Chantier en retard',
            'nouveau_message' => 'Nouveau message',
            'compte_cree' => 'Compte créé',
            default => 'Notification'
        };
    }

    private function generateMessage($type)
    {
        return match($type) {
            'nouveau_chantier' => 'Un nouveau chantier vous a été assigné.',
            'changement_statut' => 'Le statut d\'un chantier a été modifié.',
            'nouvelle_etape' => 'Une nouvelle étape a été ajoutée à votre chantier.',
            'etape_terminee' => 'Une étape de votre chantier a été terminée.',
            'nouveau_document' => 'Un nouveau document a été ajouté à votre chantier.',
            'nouveau_commentaire_client' => 'Le client a laissé un nouveau commentaire.',
            'nouveau_commentaire_commercial' => 'Le commercial a répondu à votre commentaire.',
            'chantier_retard' => 'Un chantier a dépassé sa date de fin prévue.',
            'nouveau_message' => 'Vous avez reçu un nouveau message.',
            'compte_cree' => 'Votre compte a été créé avec succès.',
            default => 'Vous avez une nouvelle notification.'
        };
    }

    // ✅ États spécifiques pour les tests
    public function unread()
    {
        return $this->state([
            'lu' => false,
            'lu_at' => null,
        ]);
    }

    public function read()
    {
        return $this->state([
            'lu' => true,
            'lu_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function systemNotification()
    {
        return $this->state([
            'chantier_id' => null,
            'type' => $this->faker->randomElement(['compte_cree', 'maintenance', 'mise_a_jour']),
        ]);
    }
}