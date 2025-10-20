<?php

use App\Models\User;
use Inertia\Inertia;
use App\Models\WalletAccountGame;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('home');

Route::get('/dashboard', function () {
    $wallet = User::find(auth()->id())->wallet;
    return inertia('Dashboard', compact('wallet'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/getOnlineStatus/{userId}', [ProfileController::class, 'getOnlineStatus'])->name('profile.getOnlineStatus');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/updateOnlineStatus', [ProfileController::class, 'updateOnlineStatus'])->name('profile.updateOnlineStatus');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Добавляем маршрут для теста Pusher
Route::get('/pusher-test', function () {
    return Inertia::render('PusherTest');
});

Route::get('/game/{id}', [GameController::class, 'show'])->name('game.show');

Route::get('/seka-game/{gameId}', function ($gameId) {
    return Inertia::render('SekaGame', ['gameId' => (int)$gameId]);
});

Route::get('/test-auth', function() {
    return [
        'user_id' => Auth::id(),
        'user' => Auth::user(),
        'check' => Auth::check()
    ];
})->middleware('auth'); // Требует аутентификации

require __DIR__ . '/auth.php';
