<?php

namespace App\Providers;

use App\Http\Middleware\Billable;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
//        if ($this->app->environment('local')) {
//            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
//            $this->app->register(TelescopeServiceProvider::class);
//        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('billable', Billable::class);

//        if ($this->app->environment('local')) {
//            $this->app['url']->forceRootUrl(env('NGROK_URL'));
//        }
    }
}
