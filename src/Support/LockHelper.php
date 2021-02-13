<?php


namespace LaravelOnce\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use LaravelOnce\Tasks\DebouncingTask;

class LockHelper
{
    public function getLockKey(string $data): string
    {
        $hash = md5($data);
        return "laravel_once:{$hash}";
    }


    public function acquireLock(string $lockKey, DebouncingTask $taskInstance): bool
    {
        $canAcquire = Cache::missing($lockKey);
        $cacheValue = (string)(new DebounceWait(Carbon::now()->timestamp, $taskInstance->wait()));
        Cache::put($lockKey, $cacheValue, $taskInstance->wait());
        return $canAcquire;
    }
}