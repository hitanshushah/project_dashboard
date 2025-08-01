<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;
use App\Http\Controllers\ProjectController;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

// Project routes
Route::get('/projects/create', function () {
    return Inertia::render('CreateProject');
})->name('projects.create');

Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');

