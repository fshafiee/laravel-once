<?php

namespace Laravel\Once\Tests\Feature;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Once\Http\Middlewares\OnceMiddleware;
use Laravel\Once\Services\OnceService;
use Laravel\Once\Tests\TestCase;

class OnceMiddlewareTest extends TestCase
{
    public function test_middleware_shouldBeInstalledGlobally()
    {
        // Arrange
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        // Assert
        $this->assertTrue($kernel->hasMiddleware(OnceMiddleware::class));
    }

    public function test_middleware_shouldCommitOnTermination()
    {
        // Arrange
        $request = Request::create('/some-url');
        $response = Response::create();
        $mockedService = $this->createPartialMock(OnceService::class, ['commit']);
        app()->instance(OnceService::class, $mockedService);

        // Assert
        $mockedService->expects($this->once())
        ->method('commit');

        // Act
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);
        $kernel->terminate($request, $response);
    }
}