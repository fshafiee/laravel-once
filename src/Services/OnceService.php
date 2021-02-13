<?php

namespace LaravelOnce\Services;

use Exception;
use LaravelOnce\Support\LockHelper;
use LaravelOnce\Tasks\RollableTask;
use LaravelOnce\Tasks\DebouncingTask;
use LaravelOnce\Job\ProcessDebouncedTask;

class OnceService
{
    /** @var RollableTask[] */
    private $tasks;
    /**
     * @var LockHelper
     */
    private $lockHelper;

    public function __construct()
    {
        $this->lockHelper = resolve(LockHelper::class);
        $this->tasks = [];
    }

    public function add(RollableTask &$task)
    {
        $this->tasks[] = $task;
    }

    public function commit(): array
    {
        $tasks = $this->dedupe();
        $results = [];
        while (count($tasks)) {
            $task = array_shift($tasks);
            /** @var RollableTask $taskInstance */
            $taskInstance = $task['instance'];
            try {
                if ($taskInstance instanceof DebouncingTask) {
                    $shouldDispatch = $this->lockHelper->acquireLock($task['lockKey'], $taskInstance);
                    if ($shouldDispatch) {
                        ProcessDebouncedTask::dispatch($taskInstance, $task['lockKey'])->delay($taskInstance->wait());
                        $results[] = 'dispatched';

                    }
                } else  {
                    $results[] = $taskInstance->perform();
                }

            } catch (Exception $e) {
                $results[] = $e;
                /**
                 * TODO: Add get optional callback to handle errors and fulfils
                 * We just don't want it to break at the moment.
                 * Proper exception handling should take place
                 * inside the perform method.
                 **/
            }
        }
        return $results;
    }

    private function dedupe(): array
    {
        $result = [];
        $backTrack = [];
        while (count($this->tasks)) {
            $task = array_shift($this->tasks);
            $serializedTask = serialize(clone $task);
            if (array_search($serializedTask, $backTrack) === false) {
                $backTrack[] = $serializedTask;
                $result[] = [
                    'instance' => $task,
                    'lockKey' => $this->lockHelper->getLockKey($serializedTask),
                ];
            }
        }
        return $result;
    }
}
