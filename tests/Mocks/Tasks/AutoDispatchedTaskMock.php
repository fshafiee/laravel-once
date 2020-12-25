<?php

namespace LaravelOnce\Tests\Mocks\Tasks;

use LaravelOnce\Tasks\AutoDispatchedTask;

class AutoDispatchedTaskMock extends AutoDispatchedTask
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
}
