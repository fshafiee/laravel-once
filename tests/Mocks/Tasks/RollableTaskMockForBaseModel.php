<?php

namespace LaravelOnce\Tests\Mocks\Tasks;

use LaravelOnce\Tests\Mocks\Models\BaseModel;
use LaravelOnce\Tasks\RollableTask;

class RollableTaskMockForBaseModel extends RollableTask
{
    /** @var BaseModel */
    private $resource;

    public function __construct(BaseModel $resource) {
        $this->resource = $resource;
    }

    public function perform()
    {
        return $this->resource->hello();
    }
}