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
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

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
        $wallet = $user->wallet;
        return Inertia::render('Dashboard', [
            'wallet' => $wallet,
            'user' => $user
        ]);
    })->name('dashboard');

    // 🎯 SEKA Game Routes
    Route::get('/seka-lobby', function () {
        $user = Auth::user();
        return Inertia::render('SekaLobby', [
            'user' => $user
        ]);
    })->name('seka.lobby');

    Route::get('/seka-game/{gameId}', function ($gameId) {
        $user = Auth::user();
        
        // Здесь можно добавить проверку доступа к игре
        $hasAccess = true; // Заглушка - в реальности проверять через GameService
        
        if (!$hasAccess) {
            abort(403, 'Access denied to this game');
        }

        return Inertia::render('SekaGame', [
            'gameId' => (int)$gameId,
            'user' => $user
        ]);
    })->name('seka.game');

    // 🎯 Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/getOnlineStatus/{userId}', [ProfileController::class, 'getOnlineStatus'])->name('profile.getOnlineStatus');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/updateOnlineStatus', [ProfileController::class, 'updateOnlineStatus'])->name('profile.updateOnlineStatus');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 🎯 Game Management
    Route::get('/game/{id}', [GameController::class, 'show'])->name('game.show');
});

require __DIR__ . '/auth.php';