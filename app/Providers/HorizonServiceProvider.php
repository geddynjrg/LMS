<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            if (!$user) {
                return false;
            }

            $allowedEmails = ['mundia@gmail.com'];

            if (app()->environment('local')) {
                return in_array($user->email, $allowedEmails);
            }

            // 2. In Production, check for a real database permission
            // This assumes you are using the Spatie Permission package (seen in your migrations)
            // return $user->can('view-horizon-dashboard') || $user->hasRole('super-admin');
            return $user->hasRole('super-admin');
        });
    }
}
