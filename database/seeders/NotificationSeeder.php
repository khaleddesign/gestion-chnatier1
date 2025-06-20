<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{User, Chantier, Notification};

class NotificationSeeder extends Seeder
{
    public function run()
    {
        // ✅ Créer des notifications d'exemple avec gestion d'erreur
        $users = User::all();
        $chantiers = Chantier::all();

        if ($users->isEmpty() || $chantiers->isEmpty()) {
            $this->command->warn('Pas d\'utilisateurs ou de chantiers pour créer des notifications');
            return;
        }

        $notifications = [
            [
                'type' => 'nouveau_chantier',
                'titre' => 'Nouveau chantier créé',
                'message' => 'Un nouveau chantier vous a été assigné.',
            ],
            [
                'type' => 'etape_terminee',
                'titre' => 'Étape terminée',
                'message' => 'Une étape de votre chantier a été terminée.',
            ],
            [
                'type' => 'nouveau_message',
                'titre' => 'Nouveau message',
                'message' => 'Vous avez reçu un nouveau message.',
            ],
            [
                'type' => 'chantier_retard',
                'titre' => 'Chantier en retard',
                'message' => 'Un de vos chantiers a dépassé sa date de fin prévue.',
            ],
        ];

        foreach ($notifications as $notifData) {
            try {
                // ✅ Sélectionner aléatoirement un utilisateur et un chantier
                $user = $users->random();
                $chantier = $chantiers->random();

                // ✅ Vérifier que l'utilisateur a accès à ce chantier
                if ($user->isClient() && $chantier->client_id !== $user->id) {
                    $chantier = $chantiers->where('client_id', $user->id)->first();
                } elseif ($user->isCommercial() && $chantier->commercial_id !== $user->id) {
                    $chantier = $chantiers->where('commercial_id', $user->id)->first();
                }

                if ($chantier) {
                    Notification::create([
                        'user_id' => $user->id,
                        'chantier_id' => $chantier->id,
                        'type' => $notifData['type'],
                        'titre' => $notifData['titre'],
                        'message' => $notifData['message'],
                        'lu' => fake()->boolean(30), // 30% de chance d'être lu
                        'lu_at' => fake()->boolean(30) ? now()->subDays(fake()->numberBetween(1, 7)) : null,
                    ]);
                }
            } catch (\Exception $e) {
                $this->command->error("Erreur création notification: " . $e->getMessage());
            }
        }

        // ✅ Créer quelques notifications système (sans chantier)
        $systemNotifications = [
            [
                'type' => 'compte_cree',
                'titre' => 'Bienvenue !',
                'message' => 'Votre compte a été créé avec succès.',
            ],
            [
                'type' => 'maintenance',
                'titre' => 'Maintenance programmée',
                'message' => 'Une maintenance est prévue ce weekend.',
            ],
        ];

        foreach ($systemNotifications as $notifData) {
            try {
                $user = $users->random();
                Notification::create([
                    'user_id' => $user->id,
                    'chantier_id' => null, // ✅ Notification système
                    'type' => $notifData['type'],
                    'titre' => $notifData['titre'],
                    'message' => $notifData['message'],
                    'lu' => fake()->boolean(50),
                    'lu_at' => fake()->boolean(50) ? now()->subDays(fake()->numberBetween(1, 3)) : null,
                ]);
            } catch (\Exception $e) {
                $this->command->error("Erreur notification système: " . $e->getMessage());
            }
        }

        $this->command->info('Notifications créées avec succès');
    }
}