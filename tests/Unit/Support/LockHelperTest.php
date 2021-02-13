<?php

namespace LaravelOnce\Tests\Unit\Support;

use Carbon\Carbon;
use LaravelOnce\Tests\TestCase;
use LaravelOnce\Support\LockHelper;
use Illuminate\Support\Facades\Cache;
use LaravelOnce\Support\DebounceWait;
use LaravelOnce\Tests\Mocks\Tasks\DebouncingTaskMock;

class LockHelperTest extends TestCase
{
    
    public function test_get_lock_key()

    {
        // Arrange
        $data = 'test';

        // Act
        $result =(new LockHelper())->getLockKey($data);

        // Assert
        $hash = md5($data);
        $expect = "laravel_once:{$hash}";
        $this->assertEquals($expect, $result);
    }

    /**
     * @dataProvider lockProvider
     */
    public function test_acquireLock_should_return_true_if_key_is_missing_in_cache($isMissing)
    {
        // Arrange
        $lockKey = 'key';
        $task = new DebouncingTaskMock("1");

        Cache::shouldReceive('missing')->with($lockKey)->andReturn($isMissing);
        Cache::shouldReceive('put')->once();
        // Act
        $result = (new LockHelper())->acquireLock($lockKey,$task);

        // Assert
        $this->assertEquals($result, $isMissing);

    }

    public function test_acquireLock_should_update_lock_ttl_on_every_call()
    {
        // Arrange && Assert
        Carbon::setTestNow(Carbon::now());
        $lockKey = 'key';
        $task = new DebouncingTaskMock("1");

        $cacheValue = (string)(new DebounceWait(Carbon::now()->timestamp, $task->wait()));
        Cache::shouldReceive('missing')->with($lockKey)->andReturn(false);
        Cache::shouldReceive('put')->with($lockKey, $cacheValue, $task->wait())->once();
        // Act
        (new LockHelper())->acquireLock($lockKey,$task);

    }

    public function lockProvider()
    {
        return [
            'missing' => [true],
            'exists' => [false],
        ];
    }

}
