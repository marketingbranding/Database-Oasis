<?php

namespace App\Providers;

use App\Models\User;
use App\UserRole;
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

    public function boot(): void
    {
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(UserRole::SuperAdmin) ? true : null;
        });
    }
}
