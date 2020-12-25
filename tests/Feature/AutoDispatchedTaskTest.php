<?php

namespace LaravelOnce\Tests\Feature;

use LaravelOnce\Services\OnceService;
use LaravelOnce\Tests\Mocks\Tasks\AutoDispatchedTaskMock;
use LaravelOnce\Tests\TestCase;

class AutoDispatchedTaskTest extends TestCase
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
