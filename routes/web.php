<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProfileController;

$domainUrl = config('app.domain_url');

if ($domainUrl) {
    Route::domain('admin.'.$domainUrl)
        ->group(function () {
            // Admin home
            Route::get('/', [ProjectController::class, 'index'])->name('admin.home');

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

            // Public projects preview route (for logged in admin)
            Route::get('/public-projects', [ProjectController::class, 'publicProjects'])->name('projects.public');

            // API routes for admin
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
            Route::patch('/api/profile/toggle-share', [ProfileController::class, 'toggleShareProfile'])->name('profile.toggle-share');

            // API routes for project data
            Route::get('/api/categories', function () {
                $user = request()->attributes->get('user');
                if (!$user) {
                    return response()->json([]);
                }
                return response()->json(\App\Models\Category::where('user_id', $user->id)
                    ->orWhereNull('user_id')
                    ->get(['id', 'name', 'key']));
            });
            Route::get('/api/statuses', fn() => response()->json(\App\Models\Status::where('is_active', true)->get(['id', 'name', 'key'])));
            Route::get('/api/link-types', fn() => response()->json(\App\Models\LinkType::all(['id', 'name', 'key'])));
            Route::get('/api/asset-types', fn() => response()->json(\App\Models\AssetType::all(['id', 'name', 'key'])));
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
                    ->flatMap(function ($name) {
                        if (is_string($name)) {
                            $decoded = json_decode($name, true);
                            return is_array($decoded) ? $decoded : [];
                        }
                        return [];
                    })
                    ->unique()
                    ->values()
                    ->toArray();

                return response()->json($technologies);
            });
        });
}

if ($domainUrl) {
    Route::domain('{subdomain}.' . $domainUrl)
        ->group(function () {
            Route::get('/', function () {
                $publicUserId = request()->attributes->get('public_user_id');

                $controller = app(ProjectController::class);
                $data = $controller->getPublicProjectsByUserId($publicUserId);

                return Inertia::render('PublicProjects', $data);
            })->name('subdomain.public-projects');

            Route::fallback(fn() => abort(404));
        });
}
