<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Notification;

class CheckNotificationAccess
{
    public function handle(Request $request, Closure $next)
    {
        $notificationId = $request->route('notification');
        
        if ($notificationId) {
            $notification = Notification::find($notificationId);
            
            if (!$notification || $notification->user_id !== auth()->id()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Accès non autorisé'], 403);
                }
                abort(403, 'Accès non autorisé à cette notification');
            }
        }

        return $next($request);
    }
}