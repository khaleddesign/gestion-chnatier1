<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Obtenir le nombre de notifications non lues
     */
    public function getUnreadCount(): JsonResponse
    {
        try {
            $count = Auth::user()->notifications()
                ->where('lu', false)
                ->count();

            return response()->json([
                'success' => true,
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du comptage des notifications'
            ], 500);
        }
    }

    /**
     * Obtenir toutes les notifications
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = Auth::user()->notifications()
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'titre' => $notification->titre,
                        'message' => $notification->message,
                        'lu' => $notification->lu,
                        'type' => $notification->type,
                        'date' => $notification->created_at->format('d/m/Y H:i'),
                        'date_relative' => $notification->created_at->diffForHumans(),
                    ];
                });

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des notifications'
            ], 500);
        }
    }

    /**
     * Voir une notification et la marquer comme lue (NOUVELLE MÉTHODE)
     */
    public function viewAndMarkAsRead($notification): JsonResponse
    {
        try {
            $notif = Auth::user()->notifications()->findOrFail($notification);
            
            // Marquer comme lue si elle ne l'est pas déjà
            if (!$notif->lu) {
                $notif->update([
                    'lu' => true,
                    'lu_at' => now()
                ]);
            }

            // Préparer l'URL de redirection si un chantier est associé
            $redirectUrl = null;
            if ($notif->chantier_id) {
                $redirectUrl = route('chantiers.show', $notif->chantier_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue',
                'redirect_url' => $redirectUrl,
                'notification' => [
                    'id' => $notif->id,
                    'titre' => $notif->titre,
                    'message' => $notif->message,
                    'lu' => true,
                    'chantier_id' => $notif->chantier_id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la consultation de la notification'
            ], 500);
        }
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($notification): JsonResponse
    {
        try {
            $notif = Auth::user()->notifications()->findOrFail($notification);
            $notif->update([
                'lu' => true,
                'lu_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $count = Auth::user()->notifications()
                ->where('lu', false)
                ->update([
                    'lu' => true,
                    'lu_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => "{$count} notification(s) marquée(s) comme lue(s)"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * Supprimer une notification
     */
    public function destroy($notification): JsonResponse
    {
        try {
            $notif = Auth::user()->notifications()->findOrFail($notification);
            $notif->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification supprimée'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}