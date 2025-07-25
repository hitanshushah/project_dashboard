<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\User;

Route::get('/', function () {
    $users = User::select('name', 'email')->get();
    return Inertia::render('Home', [
        'users' => $users,
    ]);
})->name('home');

