<?php

namespace Laravel\Once\Http\Middlewares;

use Closure;
use Laravel\Once\Services\OnceService;

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
