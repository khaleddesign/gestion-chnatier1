<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    /**
     * Les attributs assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'chantier_id',
        'subject',
        'body',
        'is_read',
        'read_at',
    ];

    /**
     * Les attributs qui doivent être convertis en dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'read_at',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Récupère l'expéditeur du message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Récupère le destinataire du message.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Récupère le chantier associé, s'il existe.
     */
    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantier::class);
    }

    /**
     * Marque le message comme lu.
     */
    public function markAsRead(): self
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return $this;
    }

    /**
     * Vérifie si le message est non lu.
     */
    public function isUnread(): bool
    {
        return !$this->is_read;
    }

    /**
     * Scope pour les messages non lus
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope pour les messages d'un utilisateur
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('recipient_id', $userId);
    }
}