<?php

namespace Laravel\Once\Tests\Mocks\Tasks;

use Exception;
use Laravel\Once\Tasks\RollableTask;

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