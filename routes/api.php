<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DevisController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\EvaluationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Ces routes sont automatiquement préfixées par /api/
| URL finale : http://localhost:8000/api/...
*/

// Route par défaut pour tester l'API
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'message' => 'API Dashboard Client fonctionnelle'
    ]);
});

// Routes publiques
Route::get('/project-types', [DevisController::class, 'getProjectTypes']);

// Routes protégées par authentification
Route::middleware(['auth:sanctum'])->group(function () {
    
    // ===== PHOTOS =====
    Route::prefix('photos')->group(function () {
        Route::get('/all', [PhotoController::class, 'getAllUserPhotos']);
        Route::get('/{photo}', [PhotoController::class, 'show']);
        Route::get('/{photo}/download', [PhotoController::class, 'download']);
        Route::post('/upload', [PhotoController::class, 'upload']);
        Route::put('/{photo}', [PhotoController::class, 'update']);
        Route::delete('/{photo}', [PhotoController::class, 'destroy']);
        Route::get('/search', [PhotoController::class, 'search']);
    });
    
    // ===== CHANTIERS =====
    Route::prefix('chantiers')->group(function () {
        Route::get('/{chantier}/photos', [PhotoController::class, 'getChantierPhotos']);
        Route::get('/{chantier}/stats', [DashboardController::class, 'getChantierStats']);
        Route::get('/{chantier}/etapes', [DashboardController::class, 'getChantierEtapes']);
        Route::get('/{chantier}/documents', [DashboardController::class, 'getChantierDocuments']);
    });
    
    // ===== DASHBOARD =====
    Route::prefix('dashboard')->group(function () {
        Route::get('/refresh', [DashboardController::class, 'refresh']);
        Route::get('/stats', [DashboardController::class, 'getStats']);
        Route::get('/activity', [DashboardController::class, 'getRecentActivity']);
        Route::get('/projects/active', [DashboardController::class, 'getActiveProjects']);
        Route::get('/progress', [DashboardController::class, 'getGlobalProgress']);
    });
    
    // ===== DEVIS =====
    Route::prefix('devis')->group(function () {
        Route::post('/', [DevisController::class, 'store']);
        Route::get('/', [DevisController::class, 'index']);
        Route::get('/{devis}', [DevisController::class, 'show']);
    });
    
    // ===== NOTIFICATIONS =====
    Route::prefix('notifications')->group(function () {
        Route::get('/count', [ApiNotificationController::class, 'getUnreadCount'])->name('api.v2.notifications.count');
        Route::get('/', [ApiNotificationController::class, 'index'])->name('api.v2.notifications.index');
        Route::get('/{notification}/view', [ApiNotificationController::class, 'viewAndMarkAsRead'])->name('api.v2.notifications.view');
        Route::put('/{notification}/read', [ApiNotificationController::class, 'markAsRead'])->name('api.v2.notifications.read');
        Route::put('/read-all', [ApiNotificationController::class, 'markAllAsRead'])->name('api.v2.notifications.read-all');
        Route::delete('/{notification}', [ApiNotificationController::class, 'destroy'])->name('api.v2.notifications.destroy');
    });
    
    // ===== ÉVALUATIONS =====
    Route::prefix('evaluations')->group(function () {
        Route::post('/', [EvaluationController::class, 'store']);
        Route::get('/', [EvaluationController::class, 'index']);
        Route::put('/{evaluation}', [EvaluationController::class, 'update']);
        Route::get('/types', [EvaluationController::class, 'getTypes']);
        Route::get('/stats', [EvaluationController::class, 'getGlobalStats']);
    });
    
    // ===== RECHERCHE =====
    Route::get('/search', [DashboardController::class, 'globalSearch']);
    
    // ===== COMMUNICATION =====
    Route::post('/communication/message', [DashboardController::class, 'sendMessage']);
});