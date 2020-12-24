<?php

namespace Laravel\Once\Tasks;

use Laravel\Once\Services\OnceService;

abstract class AutoDispatchedTask extends RollableTask
{
    public function __construct()
    {
        /** @var OnceService */
        $service = resolve(OnceService::class);
        $service->add($this);
    }
}
