<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->bind(
        //     \App\Domain\Game\Repositories\TestGameRepository::class,
        //     function () {
        //         return new \App\Domain\Game\Repositories\TestGameRepository();
        //     }
        // );

        $this->app->bind(
            \App\Domain\Game\Repositories\InMemoryGameRepository::class,
            function () {
                return new \App\Domain\Game\Repositories\InMemoryGameRepository();
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
