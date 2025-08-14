<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProfileController;

Route::get('/', [ProjectController::class, 'index'])->name('home');

// Project routes
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('/projects/{project}/toggle-visibility', [ProjectController::class, 'toggleVisibility'])->name('projects.toggle-visibility');
Route::post('/saveProject', [ProjectController::class, 'saveProject'])->name('projects.saveProject');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::post('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update.post');

// Public projects preview route
Route::get('/public-projects', [ProjectController::class, 'publicProjects'])->name('projects.public');

// Profile routes
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.remove-photo');
Route::delete('/profile/assets/{asset}', [ProfileController::class, 'removeAsset'])->name('profile.remove-asset');

// API routes for project data
Route::get('/api/categories', function () {
    return response()->json(\App\Models\Category::all(['id', 'name', 'key']));
});

Route::get('/api/statuses', function () {
    return response()->json(\App\Models\Status::where('is_active', true)->get(['id', 'name', 'key']));
});

Route::get('/api/link-types', function () {
    return response()->json(\App\Models\LinkType::all(['id', 'name', 'key']));
});

Route::get('/api/asset-types', function () {
    return response()->json(\App\Models\AssetType::all(['id', 'name', 'key']));
});

Route::get('/api/user-technologies', function () {
    $user = request()->attributes->get('user');
    if (!$user) {
        return response()->json([]);
    }
    
    $technologies = \App\Models\Tag::where('type', 'technology')
        ->where('user_id', $user->id)
        ->get()
        ->pluck('name')
        ->filter()
        ->flatMap(function($name) {
            // Extract technologies from JSON array structure
            if (is_string($name)) {
                $decoded = json_decode($name, true);
                return is_array($decoded) ? $decoded : [];
            }
            return [];
        })
        ->unique() // Show unique technologies in dropdown
        ->values()
        ->toArray();
    
    return response()->json($technologies);
});

// MinIO test route for debugging
Route::get('/api/test-minio', function () {
    try {
        $minioService = new \App\Services\MinIOService();
        $result = $minioService->testConnection();
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
});

