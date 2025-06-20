<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * ✅ Obtenir le nombre de notifications non lues
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
     * ✅ Obtenir toutes les notifications avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Auth::user()->notifications()
                ->with('chantier:id,titre')
                ->orderBy('created_at', 'desc');

            // Filtres
            if ($request->filled('filter')) {
                switch ($request->filter) {
                    case 'unread':
                        $query->where('lu', false);
                        break;
                    case 'read':
                        $query->where('lu', true);
                        break;
                    case 'type':
                        if ($request->filled('type')) {
                            $query->where('type', $request->type);
                        }
                        break;
                }
            }

            // Limite pour l'API (éviter de surcharger)
            $limit = min($request->get('limit', 20), 50);
            $notifications = $query->take($limit)->get();

            $formattedNotifications = $notifications->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'titre' => $notification->titre,
                    'message' => $notification->message,
                    'lu' => $notification->lu,
                    'type' => $notification->type,
                    'chantier' => $notification->chantier ? [
                        'id' => $notification->chantier->id,
                        'titre' => $notification->chantier->titre
                    ] : null,
                    'date' => $notification->created_at->format('d/m/Y H:i'),
                    'date_relative' => $notification->created_at->diffForHumans(),
                    'lu_at' => $notification->lu_at?->format('d/m/Y H:i'),
                ];
            });

            return response()->json([
                'success' => true,
                'notifications' => $formattedNotifications,
                'total' => $notifications->count(),
                'unread_count' => Auth::user()->notifications()->where('lu', false)->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des notifications'
            ], 500);
        }
    }

    /**
     * ✅ Marquer une notification comme lue
     */
    public function markAsRead($notification): JsonResponse
    {
        try {
            $notif = Auth::user()->notifications()->findOrFail($notification);
            
            if (!$notif->lu) {
                $notif->update([
                    'lu' => true,
                    'lu_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marquée comme lue',
                'notification' => [
                    'id' => $notif->id,
                    'lu' => true,
                    'lu_at' => $notif->lu_at->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * ✅ Marquer toutes les notifications comme lues
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
                'message' => "{$count} notification(s) marquée(s) comme lue(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour'
            ], 500);
        }
    }

    /**
     * ✅ Supprimer une notification
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
