<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ChantierController;
use App\Http\Controllers\EtapeController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\MessageController;

// ✅ IMPORTS API avec ALIAS pour éviter les conflits
use App\Http\Controllers\Api\PhotoController as ApiPhotoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Page d'accueil - Redirect vers dashboard ou login
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Routes d'authentification manuelles (Laravel UI style)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Routes d'inscription (optionnelles)
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register')->middleware('guest');
Route::post('/register', [RegisterController::class, 'register'])->middleware('guest');

// Routes de réinitialisation de mot de passe (optionnelles)
Route::get('/password/reset', function () {
    return view('auth.passwords.email');
})->name('password.request')->middleware('guest');

Route::post('/password/email', function (Illuminate\Http\Request $request) {
    $request->validate(['email' => 'required|email']);
    
    // Ici vous pouvez implémenter la logique d'envoi d'email
    // Pour l'instant, on retourne juste un message
    return back()->with('status', 'Si cette adresse email existe, vous recevrez un lien de réinitialisation.');
})->name('password.email')->middleware('guest');

// Routes protégées par l'authentification
Route::middleware(['auth'])->group(function () {
    
    // Dashboard principal (route vers le bon dashboard selon le rôle)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home'); // Fallback pour Laravel UI
    
    // ✅ ROUTES SPÉCIFIQUES AVANT LE RESOURCE (ordre CRITIQUE !)
    Route::get('chantiers/export', [ChantierController::class, 'export'])->name('chantiers.export');
    Route::get('chantiers/calendrier/view', [ChantierController::class, 'calendrier'])->name('chantiers.calendrier');
    Route::get('chantiers/search', [ChantierController::class, 'search'])->name('chantiers.search');
    
    // ✅ RESOURCE ROUTE APRÈS (pour éviter les conflits)
    Route::resource('chantiers', ChantierController::class);
    
    // Routes spécifiques avec paramètres (après le resource)
    Route::get('chantiers/{chantier}/etapes', [ChantierController::class, 'etapes'])->name('chantiers.etapes');
    
    // Gestion des étapes (nested routes)
    Route::prefix('chantiers/{chantier}')->group(function () {
        Route::post('etapes', [EtapeController::class, 'store'])->name('etapes.store');
        Route::put('etapes/{etape}', [EtapeController::class, 'update'])->name('etapes.update');
        Route::delete('etapes/{etape}', [EtapeController::class, 'destroy'])->name('etapes.destroy');
        Route::post('etapes/{etape}/toggle', [EtapeController::class, 'toggleComplete'])->name('etapes.toggle');
        Route::put('etapes/{etape}/progress', [EtapeController::class, 'updateProgress'])->name('etapes.progress');
        Route::post('etapes/reorder', [EtapeController::class, 'reorder'])->name('etapes.reorder');
        Route::get('etapes/json', [EtapeController::class, 'getEtapes'])->name('etapes.json');
    });
    
    // Gestion des documents
    Route::prefix('chantiers/{chantier}')->group(function () {
        Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');
    });
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Gestion des commentaires
    Route::prefix('chantiers/{chantier}')->group(function () {
        Route::post('commentaires', [CommentaireController::class, 'store'])->name('commentaires.store');
    });
    Route::delete('commentaires/{commentaire}', [CommentaireController::class, 'destroy'])->name('commentaires.destroy');
    
    // Notifications
    Route::middleware(['auth'])->prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        
        // ✅ ROUTE PRINCIPALE - Voir et marquer comme lue
        Route::get('/{notification}/view', [NotificationController::class, 'viewAndMarkAsRead'])->name('notifications.view');
        
        // ✅ ROUTE SÉPARÉE - Juste marquer comme lue (pour AJAX)
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        
        // ✅ ROUTE POUR TOUT MARQUER COMME LU
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    });

    // Routes pour les messages
    Route::middleware(['auth'])->prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('messages.index');
        Route::get('/sent', [MessageController::class, 'sent'])->name('messages.sent');
        Route::get('/create', [MessageController::class, 'create'])->name('messages.create');
        Route::post('/', [MessageController::class, 'store'])->name('messages.store');
        Route::get('/{message}', [MessageController::class, 'show'])->name('messages.show');
        Route::get('/{message}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    });

    // ================================
    // ROUTES API SIMPLES
    // ================================
    Route::prefix('api')->group(function () {
        // Upload de photos
        Route::post('photos/upload', [ApiPhotoController::class, 'upload'])->name('api.photos.upload');
        
        // Routes API v2 pour photos (AUTHENTIFIÉES)
        Route::prefix('v2')->group(function () {
            Route::get('chantiers/{chantier}/photos', [ApiPhotoController::class, 'getChantierPhotos'])->name('api.v2.chantiers.photos');
            Route::post('photos/upload', [ApiPhotoController::class, 'upload'])->name('api.v2.photos.upload');
            Route::get('photos/all', [ApiPhotoController::class, 'getAllUserPhotos'])->name('api.v2.photos.all');
            Route::get('photos/{photo}', [ApiPhotoController::class, 'show'])->name('api.v2.photos.show');
            Route::put('photos/{photo}', [ApiPhotoController::class, 'update'])->name('api.v2.photos.update');
            Route::delete('photos/{photo}', [ApiPhotoController::class, 'destroy'])->name('api.v2.photos.destroy');
            Route::get('photos/{photo}/download', [ApiPhotoController::class, 'download'])->name('api.v2.photos.download');
            Route::get('photos/search', [ApiPhotoController::class, 'search'])->name('api.v2.photos.search');
        });
        
        Route::get('chantiers/{chantier}/avancement', function (App\Models\Chantier $chantier) {
            // Vérification des autorisations
            if (!auth()->user()->can('view', $chantier)) {
                abort(403, 'Accès non autorisé');
            }
            
            return response()->json([
                'avancement' => $chantier->avancement_global,
                'etapes' => $chantier->etapes->map(function ($etape) {
                    return [
                        'id' => $etape->id,
                        'nom' => $etape->nom,
                        'pourcentage' => $etape->pourcentage,
                        'terminee' => $etape->terminee,
                    ];
                }),
            ]);
        })->name('api.chantiers.avancement');
        
        Route::get('notifications/count', function () {
            $count = auth()->user()->getNotificationsNonLues();
            return response()->json(['count' => $count]);
        })->name('api.notifications.count');

        Route::get('messages/unread-count', function () {
            return response()->json([
                'count' => Auth::user()->getUnreadMessagesCount()
            ]);
        })->name('api.messages.unread-count');
    });
});

// Routes admin uniquement
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::get('dashboard', [AdminController::class, 'index'])->name('admin.dashboard'); // Alias
    
    // Gestion des utilisateurs
    Route::get('users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
    Route::post('users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::get('users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::get('users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::put('users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::patch('users/{user}/toggle', [AdminController::class, 'toggleUser'])->name('admin.users.toggle');
    
    // Actions en lot et export
    Route::post('users/bulk-action', [AdminController::class, 'bulkAction'])->name('admin.users.bulk-action');
    Route::get('users/export', [AdminController::class, 'exportUsers'])->name('admin.users.export');
    
    // Statistiques
    Route::get('statistics', [AdminController::class, 'statistics'])->name('admin.statistics');
    
    // Nettoyage des fichiers orphelins (admin seulement)
    Route::post('cleanup/files', [DocumentController::class, 'cleanupOrphanedFiles'])->name('admin.cleanup.files');
});

// Route de test pour vérifier l'API
Route::get('/api/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'message' => 'API fonctionnelle'
    ]);
})->name('api.health');

// Routes d'erreur personnalisées
Route::fallback(function () {
    if (request()->expectsJson()) {
        return response()->json(['error' => 'Route non trouvée'], 404);
    }
    return response()->view('errors.404', [], 404);
});

// ===========================================
// ROUTES DE DEBUG UPLOAD (utiles pour diagnostics)
// ===========================================

// Route de test pour diagnostiquer l'upload
Route::get('/test-upload-form', function() {
    return view('test-upload');
})->name('test.upload.form');

Route::post('/test-upload-process', function(Request $request) {
    $info = [
        'timestamp' => now(),
        'php_config' => [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'memory_limit' => ini_get('memory_limit'),
        ],
        'request_info' => [
            'has_files' => $request->hasFile('test_file'),
            'files_count' => $request->hasFile('test_file') ? 1 : 0,
            'content_length' => $request->header('Content-Length'),
            'content_type' => $request->header('Content-Type'),
        ],
        'storage_info' => [
            'storage_path' => storage_path('app/public'),
            'storage_exists' => is_dir(storage_path('app/public')),
            'storage_writable' => is_writable(storage_path('app/public')),
            'public_link_exists' => is_link(public_path('storage')),
            'public_link_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : null,
        ]
    ];
    
    if ($request->hasFile('test_file')) {
        $file = $request->file('test_file');
        $info['file_info'] = [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'is_valid' => $file->isValid(),
            'error_code' => $file->getError(),
            'error_message' => $file->getErrorMessage(),
        ];
        
        try {
            // Test d'upload simple
            $path = $file->store('test', 'public');
            $info['upload_result'] = [
                'success' => true,
                'path' => $path,
                'full_path' => storage_path('app/public/' . $path),
                'file_exists' => Storage::disk('public')->exists($path),
            ];
            
            // Nettoyer le fichier test
            Storage::disk('public')->delete($path);
            
        } catch (\Exception $e) {
            $info['upload_result'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }
    
    return response()->json($info, 200, [], JSON_PRETTY_PRINT);
})->name('test.upload.process');

// Route simple pour tester la config
Route::get('/debug-config', function() {
    $info = [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'storage_path' => storage_path('app/public'),
        'storage_writable' => is_writable(storage_path('app/public')),
        'public_storage_exists' => file_exists(public_path('storage')),
        'app_url' => env('APP_URL'),
        'filesystem_disk' => env('FILESYSTEM_DISK'),
    ];
    
    return "<pre>" . print_r($info, true) . "</pre>";
});

// Test simple d'upload
Route::get('/test-upload-simple', function() {
    return '
    <!DOCTYPE html>
    <html>
    <head><title>Test Upload Simple</title></head>
    <body>
        <h2>Test Upload Simple</h2>
        <form action="/test-upload-simple" method="POST" enctype="multipart/form-data">
            ' . csrf_field() . '
            <input type="file" name="test_file" required><br><br>
            <button type="submit">Upload Test</button>
        </form>
    </body>
    </html>';
});

Route::post('/test-upload-simple', function(Request $request) {
    try {
        if (!$request->hasFile('test_file')) {
            return "Aucun fichier reçu";
        }
        
        $file = $request->file('test_file');
        
        $info = [
            'file_received' => true,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'is_valid' => $file->isValid(),
            'error' => $file->getError(),
            'error_message' => $file->getErrorMessage(),
        ];
        
        if ($file->isValid()) {
            $path = $file->store('test-uploads', 'public');
            $info['upload_success'] = true;
            $info['stored_path'] = $path;
            $info['file_exists'] = Storage::disk('public')->exists($path);
        }
        
        return "<pre>" . print_r($info, true) . "</pre>";
        
    } catch (\Exception $e) {
        return "Erreur: " . $e->getMessage() . "<br>Trace: " . $e->getTraceAsString();
    }
});