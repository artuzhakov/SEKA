<?php

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 🎯 Public Routes
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => true,
        'canRegister' => true,
        'auth' => [
            'user' => auth()->user()
        ],
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

// 🎯 Authentication routes are handled in auth.php
require __DIR__ . '/auth.php';

// 🎯 Public test routes
Route::get('/pusher-test', function () {
    return Inertia::render('PusherTest');
});

// 🎯 Authentication test route
Route::get('/test-auth', function() {
    return [
        'user_id' => Auth::id(),
        'user' => Auth::user(),
        'check' => Auth::check()
    ];
})->middleware('auth');

// 🎯 PROTECTED ROUTES (Require authentication)
Route::middleware(['auth', 'verified'])->group(function () {
    
    // 🎯 Dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();
        return Inertia::render('Dashboard', [
            'user' => $user
        ]);
    })->name('dashboard');

    // 🎯 SEKA Game Routes
    Route::get('/lobby', function () {
        $user = Auth::user();
        return Inertia::render('SekaLobby', [
            'user' => $user
        ]);
    })->name('seka.lobby');

    Route::get('/game/{id}', function ($id) {
        $user = Auth::user();
        
        return Inertia::render('SekaGame', [
            'gameId' => (int)$id,
            'user' => $user
        ]);
    })->name('game');

    // 🎯 Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});