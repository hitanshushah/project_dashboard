<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\ProjectController;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

// Project routes
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/saveProject', [ProjectController::class, 'saveProject'])->name('projects.saveProject');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

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

