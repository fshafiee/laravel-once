<?php

namespace LaravelOnce\Tests\Mocks\Tasks;

use LaravelOnce\Tasks\RollableTask;

class RollableTaskMock extends RollableTask
{
    private $resource;

    public function __construct($resource) {
        $this->resource = $resource;
    }

    public function perform()
    {
        return $this->resource;
    }
}