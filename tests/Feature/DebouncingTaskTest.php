<?php

namespace LaravelOnce\Tests\Feature;

use LaravelOnce\Tests\TestCase;
use LaravelOnce\Services\OnceService;
use LaravelOnce\Tests\Mocks\Tasks\AutoDispatchedTaskMock;

class DebouncingTaskTest extends TestCase
{
    public function test_new_shouldDispatchToServiceAutomatically()
    {
        // Arrange
        $service = resolve(OnceService::class);
        $firstResource = 'some_resource';
        $secondResource = [
            'foo' => 'bar'
        ];

        // Act
        new AutoDispatchedTaskMock($firstResource);
        new AutoDispatchedTaskMock($secondResource);
        $result = $service->commit();

        // Assert
        $this->assertEquals([
            $firstResource,
            $secondResource
        ], $result);
    }
}
