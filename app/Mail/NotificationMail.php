<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function build()
    {
        return $this->subject('Nouvelle notification - ' . $this->notification->titre)
                    ->view('emails.notification')
                    ->with([
                        'notification' => $this->notification,
                        'user' => $this->notification->user,
                        'chantier' => $this->notification->chantier,
                    ]);
    }
}