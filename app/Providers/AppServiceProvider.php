<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register model observers
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\Appointment::observe(\App\Observers\AppointmentObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
    }
}
