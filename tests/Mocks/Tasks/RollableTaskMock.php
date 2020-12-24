<?php

namespace Laravel\Once\Tests\Mocks\Tasks;

use Laravel\Once\Tasks\RollableTask;

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