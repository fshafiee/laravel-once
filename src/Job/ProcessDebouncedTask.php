<?php


namespace LaravelOnce\Job;

use Illuminate\Bus\Queueable;
use LaravelOnce\Support\DebounceWait;
use LaravelOnce\Tasks\DebouncingTask;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessDebouncedTask implements shouldQueue
{
    use Queueable;
    use InteractsWithQueue;
    use Dispatchable;

    /**
     * @var DebouncingTask
     */
    public $task;
    /**
     * @var string
     */
    public $cacheKey;

    public function __construct(DebouncingTask $task, string $cacheKey)
    {
        $this->task = $task;
        $this->cacheKey = $cacheKey;
    }


    public function handle()
    {
        // Debouncing doesnt work on sync connection
        if (!$this->isSyncConnection() && $debouncedData = Cache::get($this->cacheKey, false)) {
            $wait = DebounceWait::fromString($debouncedData)->getRemainingWaitTime();
            // dispatch the job again after wait time elapsed
            self::dispatch($this->task, $this->cacheKey)->delay($wait);
            return;
        }
        $this->task->perform();
    }

    private function isSyncConnection(): bool
    {

        if (isset($this->connection) && $this->connection === 'sync') {
            return true;
        }
        if (config('queue.default') === 'sync') {
            return true;
        }
        return false;
    }
}
