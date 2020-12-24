<?php

namespace Laravel\Once\Tests;

use Laravel\Once\Providers\OnceServiceProvider;

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