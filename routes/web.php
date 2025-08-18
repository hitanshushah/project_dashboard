<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProfileController;

Route::get('/', [ProjectController::class, 'index'])->name('home');

// Project routes
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects/update-sorting-orders', [ProjectController::class, 'updateSortingOrders'])->name('projects.update-sorting-orders');
Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::patch('/projects/{project}/toggle-visibility', [ProjectController::class, 'toggleVisibility'])->name('projects.toggle-visibility');
Route::post('/saveProject', [ProjectController::class, 'saveProject'])->name('projects.saveProject');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::post('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update.post');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

// Public projects preview route
Route::get('/public-projects', [ProjectController::class, 'publicProjects'])->name('projects.public');

// Reusable public projects routes
Route::get('/api/public-projects/user', [ProjectController::class, 'getUserForPublicProjects'])->name('api.public-projects.user');
Route::get('/api/public-projects/{userId}', [ProjectController::class, 'getPublicProjectsByUserId'])->name('api.public-projects.by-user');
Route::get('/public-projects/{userId}', [ProjectController::class, 'publicProjectsByUserId'])->name('projects.public.by-user');

// Profile routes
Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.remove-photo');
Route::delete('/profile/assets/{asset}', [ProfileController::class, 'removeAsset'])->name('profile.remove-asset');

// API route for setting public URL
Route::post('/api/profile/public-url', [ProfileController::class, 'setPublicUrl'])->name('profile.set-public-url');
Route::put('/api/profile/public-url', [ProfileController::class, 'updatePublicUrl'])->name('profile.update-public-url');
Route::delete('/api/profile/public-url', [ProfileController::class, 'deletePublicUrl'])->name('profile.delete-public-url');

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



