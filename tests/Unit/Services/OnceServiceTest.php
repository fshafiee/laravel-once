<?php

namespace Laravel\Once\Tests\Unit\Services;

use Exception;
use Laravel\Once\Services\OnceService;
use Laravel\Once\Tests\Mocks\Models\BaseModel;
use Laravel\Once\Tests\Mocks\Tasks\RollableTaskMock;
use Laravel\Once\Tests\Mocks\Tasks\RollableTaskMockForBaseModel;
use Laravel\Once\Tests\Mocks\Tasks\FaultyRollableTaskMock;
use Laravel\Once\Tests\TestCase;
class OnceServiceTest extends TestCase
{
    /** @var OnceService */
    private $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new OnceService();
    }

    public function tests_commit_should_process_all_added_tasks()
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