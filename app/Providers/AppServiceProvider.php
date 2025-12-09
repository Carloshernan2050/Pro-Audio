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
        // Registrar repositorios (Dependency Inversion Principle)
        $this->app->bind(
            \App\Repositories\Interfaces\CotizacionRepositoryInterface::class,
            \App\Repositories\CotizacionRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\SubServicioRepositoryInterface::class,
            \App\Repositories\SubServicioRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ServicioRepositoryInterface::class,
            \App\Repositories\ServicioRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\CalendarioRepositoryInterface::class,
            \App\Repositories\CalendarioRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\CalendarioItemRepositoryInterface::class,
            \App\Repositories\CalendarioItemRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\InventarioRepositoryInterface::class,
            \App\Repositories\InventarioRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\MovimientoInventarioRepositoryInterface::class,
            \App\Repositories\MovimientoInventarioRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ReservaRepositoryInterface::class,
            \App\Repositories\ReservaRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\ReservaItemRepositoryInterface::class,
            \App\Repositories\ReservaItemRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\HistorialRepositoryInterface::class,
            \App\Repositories\HistorialRepository::class
        );

        $this->app->bind(
            \App\Repositories\Interfaces\UsuarioRepositoryInterface::class,
            \App\Repositories\UsuarioRepository::class
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
