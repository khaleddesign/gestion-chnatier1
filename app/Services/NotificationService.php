<?php
namespace App\Services;

use App\Models\{Notification, User, Chantier};
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * ✅ Créer une notification de manière sécurisée
     */
    public static function create(int $userId, ?int $chantierId, string $type, string $titre, string $message): ?Notification
    {
        try {
            // ✅ Vérifications de sécurité
            $user = User::find($userId);
            if (!$user || !$user->active) {
                Log::warning("Tentative de création de notification pour utilisateur inexistant/inactif", ['user_id' => $userId]);
                return null;
            }

            // ✅ Vérifier l'accès au chantier si spécifié
            if ($chantierId) {
                $chantier = Chantier::find($chantierId);
                if (!$chantier) {
                    Log::warning("Tentative de création de notification pour chantier inexistant", ['chantier_id' => $chantierId]);
                    return null;
                }

                // ✅ Vérifier que l'utilisateur a accès à ce chantier
                if (!self::userHasAccessToChantier($user, $chantier)) {
                    Log::warning("Tentative de création de notification pour chantier non accessible", [
                        'user_id' => $userId,
                        'chantier_id' => $chantierId
                    ]);
                    return null;
                }
            }

            // ✅ Limiter le spam de notifications
            if (self::hasRecentSimilarNotification($userId, $type, $chantierId)) {
                Log::info("Notification similaire récente ignorée", [
                    'user_id' => $userId,
                    'type' => $type,
                    'chantier_id' => $chantierId
                ]);
                return null;
            }

            return Notification::create([
                'user_id' => $userId,
                'chantier_id' => $chantierId,
                'type' => $type,
                'titre' => $titre,
                'message' => $message,
                'lu' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur création notification', [
                'user_id' => $userId,
                'chantier_id' => $chantierId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * ✅ Vérifier l'accès utilisateur au chantier
     */
    private static function userHasAccessToChantier(User $user, Chantier $chantier): bool
    {
        return $user->isAdmin() || 
               $chantier->client_id === $user->id || 
               $chantier->commercial_id === $user->id;
    }

    /**
     * ✅ Éviter le spam de notifications similaires
     */
    private static function hasRecentSimilarNotification(int $userId, string $type, ?int $chantierId): bool
    {
        $query = Notification::where('user_id', $userId)
                            ->where('type', $type)
                            ->where('created_at', '>', now()->subMinutes(5));

        if ($chantierId) {
            $query->where('chantier_id', $chantierId);
        }

        return $query->exists();
    }

    /**
     * ✅ Marquer toutes les notifications d'un type comme lues
     */
    public static function markTypeAsRead(int $userId, string $type): int
    {
        return Notification::where('user_id', $userId)
                         ->where('type', $type)
                         ->where('lu', false)
                         ->update([
                             'lu' => true,
                             'lu_at' => now()
                         ]);
    }

    /**
     * ✅ Nettoyer les anciennes notifications
     */
    public static function cleanup(int $daysOld = 90): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysOld))
                         ->where('lu', true)
                         ->delete();
    }

    /**
     * ✅ Obtenir les statistiques de notifications
     */
    public static function getStats(int $userId): array
    {
        $notifications = Notification::where('user_id', $userId);

        return [
            'total' => $notifications->count(),
            'non_lues' => $notifications->where('lu', false)->count(),
            'cette_semaine' => $notifications->where('created_at', '>', now()->subWeek())->count(),
            'par_type' => $notifications->selectRaw('type, COUNT(*) as count')
                                      ->groupBy('type')
                                      ->pluck('count', 'type')
                                      ->toArray(),
        ];
    }
}