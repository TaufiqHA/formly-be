<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', function (?User $user = null) {
            // Izinkan semua user di lingkungan local
            if (app()->environment('local')) {
                return true;
            }

            // Contoh: Hanya izinkan user dengan email admin tertentu di production
            // return $user && in_array($user->email, ['admin@orderly.app']);

            return false;
        });
    }
}
