<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckApiPermission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton('mailService', function ($app) {
            return new \App\Services\MailService(); // Replace with your actual service class
        });
    }


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Route::aliasMiddleware('check-api-permission', CheckApiPermission::class);
    }
}
