<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Observers\AdministratorObserver;
use App\Observers\PostObserver;
use App\Observers\ReportObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Report::observe(ReportObserver::class);
        User::observe(AdministratorObserver::class);
        Post::observe(PostObserver::class);
    }
}
