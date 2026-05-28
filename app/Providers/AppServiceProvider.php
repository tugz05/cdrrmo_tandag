<?php

namespace App\Providers;

use App\Channels\FirebaseChannel;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Observers\AdministratorObserver;
use App\Observers\PostObserver;
use App\Observers\ReportObserver;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Contract\Messaging;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FirebaseChannel::class, function ($app) {
            return new FirebaseChannel($app->make(Messaging::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(ChannelManager::class)->extend('firebase', function ($app) {
            return $app->make(FirebaseChannel::class);
        });

        Report::observe(ReportObserver::class);
        User::observe(AdministratorObserver::class);
        Post::observe(PostObserver::class);

        $syncAppRole = function (User $user): void {
            $user->syncAppRoleFromRoles();
        };

        User::roleAdded($syncAppRole);
        User::roleRemoved($syncAppRole);
        User::roleSynced($syncAppRole);
    }
}
