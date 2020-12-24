<?php

namespace LaravelOnce\Tests;

use LaravelOnce\Providers\OnceServiceProvider;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
class TestCase extends OrchestraTestCase
{
  protected function getPackageProviders($app)
  {
    return [
        OnceServiceProvider::class,
    ];
  }
}