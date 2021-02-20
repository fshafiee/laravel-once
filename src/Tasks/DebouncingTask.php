<?php

namespace LaravelOnce\Tasks;

use LaravelOnce\Services\OnceService;

abstract class DebouncingTask extends RollableTask
{

    public function __construct()
    {
        /** @var OnceService */
        $service = resolve(OnceService::class);
        $service->add($this);
    }

    /**
     * Wait time in seconds
     * @return int
     */
    abstract public function wait(): int;
}
