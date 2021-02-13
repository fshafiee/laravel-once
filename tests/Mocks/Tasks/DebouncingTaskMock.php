<?php

namespace LaravelOnce\Tests\Mocks\Tasks;

use LaravelOnce\Tasks\DebouncingTask;

class DebouncingTaskMock extends DebouncingTask
{
    private $resource;

    public function __construct($resource)
    {
        parent::__construct();
        $this->resource = $resource;
    }

    public function perform()
    {
        return $this->resource;
    }

    public function wait(): int
    {
        return 5;
    }
}
