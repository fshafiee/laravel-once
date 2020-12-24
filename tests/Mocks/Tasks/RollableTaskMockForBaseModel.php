<?php

namespace Laravel\Once\Tests\Mocks\Tasks;

use Laravel\Once\Tests\Mocks\Models\BaseModel;
use Laravel\Once\Tasks\RollableTask;

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