<?php
// 1. CRÉER LE MODÈLE : php artisan make:model Photo
// app/Models/Photo.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'chantier_id',
        'nom',
        'chemin',
        'thumbnail',
        'description',
        'taille',
        'type_mime',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'taille' => 'integer'
    ];

    public function chantier()
    {
        return $this->belongsTo(Chantier::class);
    }

    public function getUrlAttribute()
    {
        return \Storage::url($this->chemin);
    }

    public function getThumbnailUrlAttribute()
    {
        return \Storage::url($this->thumbnail ?? $this->chemin);
    }
    public function createThumbnail($image, $width = 400)
    {
       // Installez d'abord Intervention Image si ce n'est pas déjà fait
       // composer require intervention/image
    
       $img = \Intervention\Image\Facades\Image::make($image);
       
       // Calculer la hauteur pour un ratio 16:9
       $height = round($width * 9 / 16);
       
       // Redimensionner et recadrer l'image
       $img->fit($width, $height);
       
       return $img;
    }
    public function getTailleFormateeAttribute()
    {
        if (!$this->taille) return 'Taille inconnue';
        
        $bytes = $this->taille;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

