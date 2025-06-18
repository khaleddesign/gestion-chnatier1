<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

// Imports des modèles
use App\Models\User;
use App\Models\Etape;
use App\Models\Document;
use App\Models\Commentaire;
use App\Models\Notification;

class Chantier extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'client_id',
        'commercial_id',
        'statut',
        'date_debut',
        'date_fin_prevue',
        'date_fin_effective',
        'budget',
        'notes',
        'avancement_global',
        'active',
    ];

    protected $casts = [
        'date_debut'         => 'date',
        'date_fin_prevue'    => 'date',
        'date_fin_effective' => 'date',
        'budget'             => 'decimal:2',
        'avancement_global'  => 'decimal:2',
        'active'             => 'boolean',
    ];

    // ===== RELATIONS =====
    
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    /**
     * Relation avec les étapes (version consolidée)
     */
    public function etapes()
    {
        // Vérifier si la table etapes existe
        if (Schema::hasTable('etapes')) {
            return $this->hasMany(Etape::class)->orderBy('ordre');
        }
        
        // Retourner une collection vide si la table n'existe pas
        return collect();
    }

    /**
     * Relation avec les documents (version consolidée)
     */
    public function documents()
    {
        // Vérifier si la table documents existe
        if (Schema::hasTable('documents')) {
            return $this->hasMany(Document::class)->orderBy('created_at', 'desc');
        }
        
        // Retourner une collection vide si la table n'existe pas
        return collect();
    }

    /**
     * Relation avec les photos (MISE À JOUR)
     */
    public function photos()
    {
        // Vérifier si la table photos existe et si le modèle Photo existe
        if (Schema::hasTable('photos') && class_exists(\App\Models\Photo::class)) {
            return $this->hasMany(\App\Models\Photo::class)->orderBy('created_at', 'desc');
        }
        
        // Retourner une collection vide si la table n'existe pas
        return collect();
    }

    public function commentaires()
    {
        return $this->hasMany(Commentaire::class)->orderBy('created_at', 'desc');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // ===== MÉTHODES PHOTOS (NOUVELLES) =====

    /**
     * Obtenir le nombre de photos
     */
    public function getPhotosCountAttribute()
    {
        if (Schema::hasTable('photos') && class_exists(\App\Models\Photo::class)) {
            return $this->photos()->count();
        }
        return 0;
    }

    /**
     * Obtenir les photos récentes (6 dernières)
     */
    public function getPhotosRecentesAttribute()
    {
        if (Schema::hasTable('photos') && class_exists(\App\Models\Photo::class)) {
            return $this->photos()->latest()->take(6)->get();
        }
        return collect();
    }

    /**
     * Obtenir la première photo comme image de couverture
     */
    public function getPhotoCouvertureAttribute()
    {
        if (Schema::hasTable('photos') && class_exists(\App\Models\Photo::class)) {
            return $this->photos()->latest()->first();
        }
        return null;
    }

    /**
     * Vérifier si le chantier a des photos
     */
    public function hasPhotos()
    {
        return $this->photos_count > 0;
    }

    // ===== MÉTHODES MÉTIER =====

    /**
     * Calculer l'avancement global basé sur les étapes
     */
    public function calculerAvancement()
    {
        $etapes = $this->etapes;
        
        // Si etapes retourne une collection vide ou nulle
        if (!$etapes || $etapes->count() === 0) {
            return 0;
        }

        $total = $etapes->sum('pourcentage');
        $moyenne = $total / $etapes->count();

        $this->update(['avancement_global' => $moyenne]);
        return $moyenne;
    }

    /**
     * Vérifier si le chantier est en retard (version unique)
     */
    public function isEnRetard()
    {
        return $this->date_fin_prevue && 
               $this->date_fin_prevue->isPast() && 
               $this->statut !== 'termine';
    }

    // ===== ACCESSORS ET MUTATORS =====

    /**
     * Accessor pour gérer l'avancement global
     */
    public function getAvancementGlobalAttribute($value)
    {
        return $value ?? 0;
    }

    // ===== MÉTHODES POUR L'AFFICHAGE (TAILWIND CSS) =====

    /**
     * Retourne les classes Tailwind CSS pour le badge de statut
     */
    public function getStatutBadgeClass()
    {
        return match ($this->statut) {
            'planifie' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
            'en_cours' => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
            'termine'  => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
            default    => 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
        };
    }

    /**
     * Retourne la couleur Tailwind pour la barre de progression
     */
    public function getProgressBarColor()
    {
        return match ($this->statut) {
            'planifie' => 'bg-gray-400',
            'en_cours' => 'bg-blue-500',
            'termine'  => 'bg-green-500',
            default    => 'bg-gray-400',
        };
    }

    /**
     * Retourne l'icône correspondant au statut (FontAwesome)
     */
    public function getStatutIcon()
    {
        return match ($this->statut) {
            'planifie' => 'fas fa-clock',
            'en_cours' => 'fas fa-play',
            'termine'  => 'fas fa-check-circle',
            default    => 'fas fa-question-circle',
        };
    }

    /**
     * Retourne le texte du statut
     */
    public function getStatutTexte()
    {
        return match ($this->statut) {
            'planifie' => 'Planifié',
            'en_cours' => 'En cours',
            'termine'  => 'Terminé',
            default    => 'Inconnu',
        };
    }

    /**
     * Retourne les classes CSS pour indiquer un retard
     */
    public function getRetardClass()
    {
        if ($this->isEnRetard()) {
            return 'text-red-600 font-semibold';
        }
        return '';
    }

    // ===== SCOPES =====

    /**
     * Scope pour les chantiers actifs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope pour les chantiers par statut
     */
    public function scopeAvecStatut($query, $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope pour les chantiers en retard
     */
    public function scopeEnRetard($query)
    {
        return $query->where('date_fin_prevue', '<', now())
                    ->where('statut', '!=', 'termine');
    }

    // ===== MÉTHODES UTILITAIRES =====

    /**
     * Retourne le pourcentage d'avancement formaté
     */
    public function getAvancementFormate()
    {
        return number_format($this->avancement_global, 0) . '%';
    }

    /**
     * Retourne la durée prévue du chantier
     */
    public function getDureePrevue()
    {
        if (!$this->date_debut || !$this->date_fin_prevue) {
            return null;
        }

        return $this->date_debut->diffInDays($this->date_fin_prevue);
    }

    /**
     * Retourne le budget formaté
     */
    public function getBudgetFormate()
    {
        if (!$this->budget) {
            return 'Non défini';
        }

        return number_format($this->budget, 0, ',', ' ') . ' €';
    }

    /**
     * Vérifie si le chantier peut être modifié
     */
    public function peutEtreModifie()
    {
        return $this->statut !== 'termine';
    }
}