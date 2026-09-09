<?php

namespace App\Providers;

use App\Enums\NameOfRoles;
use App\Models\User;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('adminJob', function (User $user) {

            return $user->hasRole(NameOfRoles::SuperAdmin->value);
        });
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        Gate::define('ownerJob', function (User $user) {

            return $user->hasRole(NameOfRoles::Owner->value);
        });
    }
}
