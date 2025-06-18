<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\NotificationCreated;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'chantier_id', 'type', 'titre', 'message', 'lu', 'lu_at'
    ];

    protected $casts = [
        'lu' => 'boolean',
        'lu_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($notification) {
            event(new NotificationCreated($notification));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function chantier()
    {
        return $this->belongsTo(Chantier::class);
    }

    public function marquerLue()
    {
        $this->update([
            'lu' => true,
            'lu_at' => now()
        ]);
    }

    public static function creerNotification($userId, $chantierId, $type, $titre, $message)
    {
        return static::create([
            'user_id' => $userId,
            'chantier_id' => $chantierId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message
        ]);
    }
}