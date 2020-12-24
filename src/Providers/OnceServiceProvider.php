<?php
namespace LaravelOnce\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use LaravelOnce\Http\Middlewares\OnceMiddleware;
use LaravelOnce\Services\OnceService;

class OnceServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /**
         * Add service to service contianer.
         * OnceService is responsible for collection and
         * deduplication of the tasks.
         */
        $this->app->singleton(OnceService::class, function ($app) {
            return new OnceService();
        });

        /**
         * Add the terminable middleware so that
         * side-effects are processed after the
         * request is served
         */
        $kernel = $this->app->make(Kernel::class);
        $kernel->pushMiddleware(OnceMiddleware::class);

        /**
         * Make sure jobs also commit the tasks when
         * they are finished
         */
        Queue::after(function (JobProcessed $event) {
            resolve(OnceService::class)->commit();
        });
    }
}
