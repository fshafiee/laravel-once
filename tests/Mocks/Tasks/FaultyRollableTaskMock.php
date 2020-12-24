<?php

namespace LaravelOnce\Tests\Mocks\Tasks;

use Exception;
use LaravelOnce\Tasks\RollableTask;

class FaultyRollableTaskMock extends RollableTask
{
    public $resource;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public function perform()
    {
        throw new Exception("Ooops!");
    }
}