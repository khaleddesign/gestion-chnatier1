<?php

namespace App\Mail;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Créer une nouvelle instance de message.
     */
    public function __construct(public Message $message)
    {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Gestion Chantiers] ' . $this->message->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.messages.new',
            with: [
                'message' => $this->message,
                'url' => route('messages.show', $this->message),
            ],
        );
    }
}
