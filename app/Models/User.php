<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'telephone',
        'adresse',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
        ];
    }

    // ===== RELATIONS =====

    /**
     * Relation avec les chantiers où l'utilisateur est client
     * (Alias pour compatibilité avec le DashboardController)
     */
    public function chantiers()
    {
        return $this->hasMany(\App\Models\Chantier::class, 'client_id');
    }

    /**
     * Relation avec les chantiers où l'utilisateur est client
     * (Nom explicite)
     */
    public function chantiersClient()
    {
        return $this->hasMany(\App\Models\Chantier::class, 'client_id');
    }

    /**
     * Relation avec les chantiers où l'utilisateur est commercial
     */
    public function chantiersCommercial()
    {
        return $this->hasMany(\App\Models\Chantier::class, 'commercial_id');
    }

    /**
     * Relation avec les notifications
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }

    /**
     * Relation avec les commentaires
     */
    public function commentaires()
    {
        return $this->hasMany(\App\Models\Commentaire::class);
    }

    /**
     * Relation avec les documents
     */
    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class);
    }

    /**
     * Relation avec les messages envoyés
     */
    public function sentMessages()
    {
        return $this->hasMany(\App\Models\Message::class, 'sender_id');
    }

    /**
     * Relation avec les messages reçus
     */
    public function receivedMessages()
    {
        return $this->hasMany(\App\Models\Message::class, 'recipient_id');
    }

    /**
     * Relation avec les devis (si la table existe)
     */
    public function devis()
    {
        if (\Schema::hasTable('devis')) {
            return $this->hasMany(\App\Models\Devis::class, 'client_id');
        }
        return collect();
    }

    /**
     * Relation avec les évaluations (si la table existe)
     */
    public function evaluations()
    {
        if (\Schema::hasTable('evaluations')) {
            return $this->hasMany(\App\Models\Evaluation::class);
        }
        return collect();
    }

    // ===== MÉTHODES UTILITAIRES POUR LES RÔLES =====

    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est commercial
     */
    public function isCommercial(): bool
    {
        return $this->role === 'commercial';
    }

    /**
     * Vérifier si l'utilisateur est client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // ===== MÉTHODES POUR LES NOTIFICATIONS =====

    /**
     * Compter les notifications non lues
     */
    public function getNotificationsNonLues(): int
    {
        return $this->notifications()->where('lu', false)->count();
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function marquerNotificationsLues(): int
    {
        return $this->notifications()->where('lu', false)->update(['lu' => true]);
    }

    // ===== MÉTHODES POUR LES MESSAGES =====

    /**
     * Obtenir le nombre de messages non lus
     */
    public function getUnreadMessagesCount(): int
    {
        return $this->receivedMessages()->where('is_read', false)->count();
    }

    /**
     * Marquer tous les messages comme lus
     */
    public function markAllMessagesAsRead(): int
    {
        return $this->receivedMessages()->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

    // ===== MÉTHODES POUR LES CHANTIERS =====

    /**
     * Obtenir les chantiers selon le rôle
     */
    public function getChantiers()
    {
        switch ($this->role) {
            case 'admin':
                return \App\Models\Chantier::all();
            case 'commercial':
                return $this->chantiersCommercial;
            case 'client':
                return $this->chantiersClient;
            default:
                return collect();
        }
    }

    /**
     * Obtenir les chantiers actifs selon le rôle
     */
    public function getChantiersActifs()
    {
        switch ($this->role) {
            case 'admin':
                return \App\Models\Chantier::where('statut', 'en_cours')->get();
            case 'commercial':
                return $this->chantiersCommercial()->where('statut', 'en_cours')->get();
            case 'client':
                return $this->chantiersClient()->where('statut', 'en_cours')->get();
            default:
                return collect();
        }
    }

    // ===== STATISTIQUES =====

    /**
     * Obtenir les statistiques pour l'utilisateur
     */
    public function getStats(): array
    {
        $stats = [
            'total_chantiers' => 0,
            'chantiers_en_cours' => 0,
            'chantiers_termines' => 0,
            'chantiers_planifies' => 0,
            'notifications_non_lues' => $this->getNotificationsNonLues(),
            'messages_non_lus' => $this->getUnreadMessagesCount(),
            'avancement_moyen' => 0,
        ];

        if ($this->isAdmin()) {
            $stats['total_chantiers'] = \App\Models\Chantier::count();
            $stats['chantiers_en_cours'] = \App\Models\Chantier::where('statut', 'en_cours')->count();
            $stats['chantiers_termines'] = \App\Models\Chantier::where('statut', 'termine')->count();
            $stats['chantiers_planifies'] = \App\Models\Chantier::where('statut', 'planifie')->count();
            $stats['avancement_moyen'] = \App\Models\Chantier::avg('avancement_global') ?: 0;
        } elseif ($this->isCommercial()) {
            $chantiers = $this->chantiersCommercial();
            $stats['total_chantiers'] = $chantiers->count();
            $stats['chantiers_en_cours'] = $chantiers->where('statut', 'en_cours')->count();
            $stats['chantiers_termines'] = $chantiers->where('statut', 'termine')->count();
            $stats['chantiers_planifies'] = $chantiers->where('statut', 'planifie')->count();
            $stats['avancement_moyen'] = $chantiers->avg('avancement_global') ?: 0;
        } elseif ($this->isClient()) {
            $chantiers = $this->chantiersClient();
            $stats['total_chantiers'] = $chantiers->count();
            $stats['chantiers_en_cours'] = $chantiers->where('statut', 'en_cours')->count();
            $stats['chantiers_termines'] = $chantiers->where('statut', 'termine')->count();
            $stats['chantiers_planifies'] = $chantiers->where('statut', 'planifie')->count();
            $stats['avancement_moyen'] = $chantiers->avg('avancement_global') ?: 0;
        }

        return $stats;
    }

    // ===== SCOPES =====

    /**
     * Scope pour filtrer les utilisateurs actifs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour filtrer par rôle
     */
    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope pour les commerciaux actifs
     */
    public function scopeCommerciaux($query)
    {
        return $query->where('role', 'commercial')->where('active', true);
    }

    /**
     * Scope pour les clients actifs
     */
    public function scopeClients($query)
    {
        return $query->where('role', 'client')->where('active', true);
    }

    // ===== MÉTHODES D'AFFICHAGE =====

    /**
     * Obtenir le nom d'affichage du rôle
     */
    public function getRoleDisplayName(): string
    {
        return match ($this->role) {
            'admin' => 'Administrateur',
            'commercial' => 'Commercial',
            'client' => 'Client',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir les initiales de l'utilisateur
     */
    public function getInitials(): string
    {
        $names = explode(' ', $this->name);
        $initials = '';
        
        foreach ($names as $name) {
            $initials .= substr($name, 0, 1);
        }
        
        return strtoupper(substr($initials, 0, 2));
    }

    /**
     * Obtenir la couleur du badge selon le rôle
     */
    public function getRoleBadgeClass(): string
    {
        return match ($this->role) {
            'admin' => 'bg-red-100 text-red-800',
            'commercial' => 'bg-blue-100 text-blue-800',
            'client' => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // ===== MÉTHODES DE VALIDATION =====

    /**
     * Vérifier si l'utilisateur peut accéder à un chantier
     */
    public function canAccessChantier(\App\Models\Chantier $chantier): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isCommercial() && $chantier->commercial_id === $this->id) {
            return true;
        }
        
        if ($this->isClient() && $chantier->client_id === $this->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Vérifier si l'utilisateur peut modifier un chantier
     */
    public function canEditChantier(\App\Models\Chantier $chantier): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isCommercial() && $chantier->commercial_id === $this->id) {
            return true;
        }
        
        return false;
    }
}