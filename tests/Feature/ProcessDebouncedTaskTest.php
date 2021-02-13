<?php

namespace LaravelOnce\Tests\Feature;

use Mockery;
use Carbon\Carbon;
use LaravelOnce\Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use LaravelOnce\Support\DebounceWait;
use LaravelOnce\Job\ProcessDebouncedTask;
use LaravelOnce\Tests\Mocks\Tasks\DebouncingTaskMock;

class ProcessDebouncedTaskTest extends TestCase
{
    public function test_handle_should_release_itself_again_to_queue_when_lock_exists()
    {
        // Arrange
        Bus::fake();
        config(['queue.default' => 'redis']);
        Carbon::setTestNow(Carbon::now());
        $lockKey = 'key';
        $task = Mockery::spy(new DebouncingTaskMock("1"));

        $cacheValue = (string)(new DebounceWait(Carbon::now()->timestamp, $remainingWait = 3));
        Cache::shouldReceive('get')->with($lockKey, false)->andReturn($cacheValue)->once();
        // Act
        (new ProcessDebouncedTask($task, $lockKey))->handle();

        // Assert
        $task->shouldNotHaveReceived('perform');
        Bus::assertDispatched(ProcessDebouncedTask::class,
            function ($job) use ($task, $lockKey, $remainingWait) {
                return
                    $job->task === $task
                    && $job->delay === $remainingWait
                    && $job->cacheKey === $lockKey ;
            }
        );
    }

    public function test_handle_should_perform_when_there_is_no_lock()
    {
        // Arrange
        Bus::fake();
        config(['queue.default' => 'redis']);
        Carbon::setTestNow(Carbon::now());
        $lockKey = 'key';
        $task = Mockery::spy(new DebouncingTaskMock("1"));

        Cache::shouldReceive('get')->andReturn(false)->once();
        // Act
        (new ProcessDebouncedTask($task, $lockKey))->handle();

        // Assert
        $task->shouldHaveReceived('perform')->once();
        Bus::assertNotDispatched(ProcessDebouncedTask::class);
    }

    public function test_handle_should_perform_when_default_queue_is_sync()
    {
        // Arrange
        Bus::fake();
        config(['queue.default' => 'sync']);
        Carbon::setTestNow(Carbon::now());
        $lockKey = 'key';
        $task = Mockery::spy(new DebouncingTaskMock("1"));

        $cacheValue = (string)(new DebounceWait(Carbon::now()->timestamp, $remainingWait = 3));
        Cache::shouldReceive('get')->with($lockKey, false)->andReturn($cacheValue)->never();
        // Act
        (new ProcessDebouncedTask($task, $lockKey))->handle();

        // Assert
        $task->shouldHaveReceived('perform');
        Bus::assertNotDispatched(ProcessDebouncedTask::class);
    }


}
