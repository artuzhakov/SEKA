<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Game\Repositories\GameRepositoryInterface;
use App\Domain\Game\Repositories\InMemoryGameRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 🎯 ПРАВИЛЬНАЯ РЕГИСТРАЦИЯ SINGLETON
        $this->app->singleton(
            GameRepositoryInterface::class,
            function () {
                return InMemoryGameRepository::getInstance();
            }
        );

        // 🎯 АЛЬТЕРНАТИВНО: если нужен новый инстанс каждый раз
        // $this->app->bind(
        //     GameRepositoryInterface::class,
        //     function () {
        //         return new InMemoryGameRepository();
        //     }
        // );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}