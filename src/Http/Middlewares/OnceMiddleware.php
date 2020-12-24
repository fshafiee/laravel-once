<?php

namespace LaravelOnce\Http\Middlewares;

use Closure;
use LaravelOnce\Services\OnceService;

class OnceMiddleware
{
    /** @var OnceService */
    private $service;

    public function __construct(OnceService $service)
    {
        $this->service = $service;
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $this->service->commit();
    }
}
