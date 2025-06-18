<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendNotificationEmail implements ShouldQueue
{
    public function handle(NotificationCreated $event)
    {
        $notification = $event->notification;
        $user = $notification->user;

        if (!$user->email_notifications) {
            return;
        }

        if ($user->email_preferences) {
            $preferences = json_decode($user->email_preferences, true);
            if (isset($preferences[$notification->type]) && !$preferences[$notification->type]) {
                return;
            }
        }

        try {
            Mail::to($user->email)->send(new NotificationMail($notification));
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email notification: ' . $e->getMessage());
        }
    }
}