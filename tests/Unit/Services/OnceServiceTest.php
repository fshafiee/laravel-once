<?php

namespace LaravelOnce\Tests\Unit\Services;

use Mockery;
use Exception;
use LaravelOnce\Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use LaravelOnce\Support\LockHelper;
use LaravelOnce\Services\OnceService;
use LaravelOnce\Job\ProcessDebouncedTask;
use LaravelOnce\Tests\Mocks\Models\BaseModel;
use LaravelOnce\Tests\Mocks\Tasks\RollableTaskMock;
use LaravelOnce\Tests\Mocks\Tasks\DebouncingTaskMock;
use LaravelOnce\Tests\Mocks\Tasks\FaultyRollableTaskMock;
use LaravelOnce\Tests\Mocks\Tasks\RollableTaskMockForBaseModel;

class OnceServiceTest extends TestCase
{
    /** @var OnceService */
    private $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new OnceService();
    }

    public function test_commit_should_process_all_added_tasks()
    {
        // Arrange
        $task = new RollableTaskMock('1');
        $task2 = new RollableTaskMock('2');

        // Act
        $this->service->add($task);
        $this->service->add($task2);
        $result = $this->service->commit();

        // Assert
        $this->assertEquals(['1', '2'], $result);
    }

    public function test_commit_should_queue_debouncing_tasks()
    {
        // Arrange
        $task = new DebouncingTaskMock('1');
        $task2 = new DebouncingTaskMock('2');
        Bus::fake([ProcessDebouncedTask::class]);

        // Act
        $this->service->add($task);
        $this->service->add($task2);
        $this->service->commit();

        // Assert
        Bus::assertDispatchedTimes(ProcessDebouncedTask::class, 2);
    }

    public function test_commit_should_dispatch_and_delay_the_job_based_on_wait_time_when_able_to_acquire_lock()
    {
        // Arrange
        Bus::fake();
        $mock = Mockery::mock(LockHelper::class)->makePartial();
        $mock->shouldReceive('acquireLock')->andReturn(true);
        $mock->shouldReceive('getLockKey')->andReturn($lockKey = 'lock-key');
        $this->app->instance(LockHelper::class, $mock);


        $service = new OnceService();

        $task = new DebouncingTaskMock('1');


        // Act
        $service->add($task);
        $service->commit();

        // Assert

        Bus::assertDispatched(
            ProcessDebouncedTask::class,
            function ($job) use ($task, $lockKey) {
                return
                    $job->task === $task
                    && $job->delay === $task->wait()
                    && $job->cacheKey === $lockKey ;
            }
        );
    }
    public function test_commit_should_not_dispatch_the_job_when_is_not_able_to_acquire_lock()
    {
        $this->withExceptionHandling();
        // Arrange
        Bus::fake();
        $mock = Mockery::mock(LockHelper::class)->makePartial();
        $mock->shouldReceive('getLockKey')->andReturn($lockKey = 'lock-key');
        $mock->shouldReceive('acquireLock')->andReturn(false);
        $this->app->instance(LockHelper::class, $mock);


        $service = new OnceService();

        $task = new DebouncingTaskMock('1');


        // Act
        $service->add($task);
        $service->commit();

        // Assert

        Bus::assertNotDispatched(ProcessDebouncedTask::class);
    }


    public function test_commit_should_process_duplicate_tasks_only_once()
    {
        // Arrange

        $task = new RollableTaskMock('some_resource_id');
        $duplicateTask = new RollableTaskMock('some_resource_id');
        $triplicateTask = new RollableTaskMock('some_resource_id');

        // Act
        $this->service->add($task);
        $this->service->add($duplicateTask);
        $this->service->add($triplicateTask);
        $result = $this->service->commit();

        // Assert
        $this->assertEquals(['some_resource_id'], $result);
    }

    public function test_commit_should_continue_processing_tasks_when_exceptions_occur()
    {
        // Arrange
        $task = new RollableTaskMock('id_1');
        $faultyTask = new FaultyRollableTaskMock('id_2');
        $task2 = new RollableTaskMock('id_3');
        $duplicateTask = new RollableTaskMock('id_3');

        // Act
        $this->service->add($task);
        $this->service->add($faultyTask);
        $this->service->add($task2);
        $this->service->add($duplicateTask);
        $result = $this->service->commit();

        // Assert
        $this->assertEquals([
            'id_1',
            new Exception("Ooops!"),
            'id_3'
        ], $result);
    }

    public function test_should_work_with_eloquent_models()
    {
        // Arrange
        $model = new BaseModel(['_id' => 'foo']);
        $secondaryModel = new BaseModel(['_id' => 'bar']);

        $task = new RollableTaskMockForBaseModel($model);
        $secondaryTask = new RollableTaskMockForBaseModel($secondaryModel);

        // Act
        $this->service->add($task);
        $this->service->add($secondaryTask);
        $result = $this->service->commit();

        // Assert
        $this->assertEquals(['foo', 'bar'], $result);
    }

    public function test_should_dedupe_tasks_when_working_with_eloquent_models()
    {
        // Arrange
        $model = new BaseModel(['_id' => 'foo']);
        $duplicateModel = new BaseModel(['_id' => 'foo']);

        $task = new RollableTaskMockForBaseModel($model);
        $duplicateTask = new RollableTaskMockForBaseModel($model);
        $triplicateTask = new RollableTaskMockForBaseModel($duplicateModel);

        // Act
        $this->service->add($task);
        $this->service->add($duplicateTask);
        $this->service->add($triplicateTask);
        $result = $this->service->commit();

        // Assert
        $this->assertEquals(['foo'], $result);
    }
}
