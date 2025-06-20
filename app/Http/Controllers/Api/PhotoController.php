<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use App\Models\Chantier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class PhotoController extends Controller
{
    /**
     * Obtenir toutes les photos de l'utilisateur
     */
    public function getAllUserPhotos(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Vérifier que l'utilisateur est connecté
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            $query = Photo::query();
            
            // Filtrer selon le rôle de l'utilisateur
            if ($user->isClient()) {
                $query->whereHas('chantier', function ($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            } elseif ($user->isCommercial()) {
                $query->whereHas('chantier', function ($q) use ($user) {
                    $q->where('commercial_id', $user->id);
                });
            } // Les admins peuvent voir toutes les photos
            
            $photos = $query->with(['chantier:id,titre'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'nom' => $photo->nom,
                        'url' => Storage::url($photo->chemin),
                        'thumbnail' => Storage::url($photo->thumbnail ?? $photo->chemin),
                        'chantier' => $photo->chantier->titre,
                        'chantier_id' => $photo->chantier->id,
                        'date' => $photo->created_at->format('d/m/Y'),
                        'taille' => $photo->taille ?? 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'photos' => $photos,
                'total' => $photos->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des photos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les photos d'un chantier spécifique
     */
    public function getChantierPhotos(Chantier $chantier): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            // Vérifier que l'utilisateur a accès à ce chantier
            $canAccess = $user->isAdmin() || 
                         $chantier->client_id === $user->id || 
                         $chantier->commercial_id === $user->id;
                         
            if (!$canAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $photos = $chantier->photos()
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'nom' => $photo->nom,
                        'url' => Storage::url($photo->chemin),
                        'thumbnail' => Storage::url($photo->thumbnail ?? $photo->chemin),
                        'description' => $photo->description,
                        'date' => $photo->created_at->format('d/m/Y H:i'),
                        'taille' => $photo->taille ?? 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'photos' => $photos,
                'chantier' => [
                    'id' => $chantier->id,
                    'titre' => $chantier->titre
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des photos du chantier: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une photo spécifique
     */
    public function show(Photo $photo): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            // Vérifier l'accès
            $chantier = $photo->chantier;
            $canAccess = $user->isAdmin() || 
                         $chantier->client_id === $user->id || 
                         $chantier->commercial_id === $user->id;
                         
            if (!$canAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'photo' => [
                    'id' => $photo->id,
                    'nom' => $photo->nom,
                    'url' => Storage::url($photo->chemin),
                    'thumbnail' => Storage::url($photo->thumbnail ?? $photo->chemin),
                    'description' => $photo->description,
                    'chantier' => $photo->chantier->titre,
                    'date' => $photo->created_at->format('d/m/Y H:i'),
                    'taille' => $photo->taille ?? 0,
                    'metadata' => $photo->metadata ?? [],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Photo non trouvée: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Télécharger une photo
     */
    public function download(Photo $photo)
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                abort(401, 'Authentification requise');
            }
            
            // Vérifier l'accès
            $chantier = $photo->chantier;
            $canAccess = $user->isAdmin() || 
                         $chantier->client_id === $user->id || 
                         $chantier->commercial_id === $user->id;
                         
            if (!$canAccess) {
                abort(403, 'Accès non autorisé');
            }

            $filePath = storage_path('app/' . $photo->chemin);
            
            if (!file_exists($filePath)) {
                abort(404, 'Fichier non trouvé');
            }

            $fileName = $photo->nom ?: 'photo_' . $photo->id . '.jpg';

            return response()->download($filePath, $fileName);

        } catch (\Exception $e) {
            abort(500, 'Erreur lors du téléchargement: ' . $e->getMessage());
        }
    }

    /**
     * Upload de nouvelles photos (version simplifiée sans Intervention Image)
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'chantier_id' => 'required|exists:chantiers,id',
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            $chantier = Chantier::findOrFail($request->chantier_id);
            
            // Vérifier l'accès
            $canAccess = $user->isAdmin() || 
                         $chantier->client_id === $user->id || 
                         $chantier->commercial_id === $user->id;
                         
            if (!$canAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à ce chantier'
                ], 403);
            }

            $uploadedPhotos = [];
            $errorCount = 0;

            foreach ($request->file('photos') as $file) {
                try {
                    // Générer un nom unique
                    $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    
                    // Définir le chemin
                    $originalDir = 'photos/chantiers/' . $chantier->id;
                    $originalPath = $originalDir . '/' . $fileName;
                    
                    // Stocker l'image
                    $path = $file->storeAs($originalDir, $fileName, 'public');
                    
                    if (!$path) {
                        $errorCount++;
                        continue;
                    }
                    
                    // Créer l'enregistrement en base
                    $photo = Photo::create([
                        'chantier_id' => $chantier->id,
                        'nom' => $file->getClientOriginalName(),
                        'chemin' => 'public/' . $path,
                        'thumbnail' => 'public/' . $path, // Même image pour l'instant
                        'taille' => $file->getSize(),
                        'type_mime' => $file->getMimeType(),
                        'metadata' => [
                            'original_name' => $file->getClientOriginalName(),
                            'uploaded_by' => $user->id,
                            'upload_date' => now(),
                        ]
                    ]);

                    $uploadedPhotos[] = [
                        'id' => $photo->id,
                        'nom' => $photo->nom,
                        'url' => Storage::url($photo->chemin),
                        'thumbnail' => Storage::url($photo->thumbnail),
                    ];

                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::error('Erreur upload photo: ' . $e->getMessage());
                }
            }

            $successCount = count($uploadedPhotos);
            $message = $successCount > 0 ? 
                "{$successCount} photo(s) uploadée(s) avec succès" : 
                "Aucune photo n'a pu être uploadée";

            if ($errorCount > 0) {
                $message .= " ({$errorCount} erreur(s))";
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'count' => $successCount,
                'errors' => $errorCount,
                'photos' => $uploadedPhotos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload des photos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une photo
     */
    public function update(Request $request, Photo $photo): JsonResponse
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
        ]);

        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            // Vérifier l'accès
            $chantier = $photo->chantier;
            $canEdit = $user->isAdmin() || 
                       $chantier->client_id === $user->id || 
                       $chantier->commercial_id === $user->id;
                       
            if (!$canEdit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            $photo->update($request->only(['nom', 'description']));

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'photo' => [
                    'id' => $photo->id,
                    'nom' => $photo->nom,
                    'description' => $photo->description,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une photo
     */
    public function destroy(Photo $photo): JsonResponse
    {
        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            // Vérifier l'accès
            $chantier = $photo->chantier;
            $canDelete = $user->isAdmin() || 
                        $chantier->client_id === $user->id || 
                        $chantier->commercial_id === $user->id;
                        
            if (!$canDelete) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Supprimer le fichier physique
            if (Storage::exists($photo->chemin)) {
                Storage::delete($photo->chemin);
            }
            
            // Supprimer la miniature si elle existe
            if ($photo->thumbnail && Storage::exists($photo->thumbnail)) {
                Storage::delete($photo->thumbnail);
            }

            // Supprimer l'enregistrement
            $photo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Photo supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechercher dans les photos
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'chantier_id' => 'sometimes|exists:chantiers,id',
        ]);

        try {
            // Vérifier que l'utilisateur est connecté
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentification requise'
                ], 401);
            }
            
            $query = Photo::query();
            
            // Filtrer selon le rôle
            if ($user->isClient()) {
                $query->whereHas('chantier', function ($q) use ($user) {
                    $q->where('client_id', $user->id);
                });
            } elseif ($user->isCommercial()) {
                $query->whereHas('chantier', function ($q) use ($user) {
                    $q->where('commercial_id', $user->id);
                });
            }

            // Filtrer par chantier si spécifié
            if ($request->has('chantier_id')) {
                $query->where('chantier_id', $request->chantier_id);
            }

            // Recherche dans le nom et la description
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nom', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });

            $photos = $query->with('chantier:id,titre')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'nom' => $photo->nom,
                        'url' => Storage::url($photo->chemin),
                        'thumbnail' => Storage::url($photo->thumbnail ?? $photo->chemin),
                        'chantier' => $photo->chantier->titre,
                        'date' => $photo->created_at->format('d/m/Y'),
                    ];
                });

            return response()->json([
                'success' => true,
                'results' => $photos,
                'count' => $photos->count(),
                'query' => $searchTerm
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
            ], 500);
        }
    }
}