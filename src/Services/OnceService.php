<?php

namespace Laravel\Once\Services;

use Exception;
use Laravel\Once\Tasks\RollableTask;

class OnceService
{
    /** @var RollableTask[] */
    private $tasks;

    public function __construct()
    {
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
            /** @var RollableTask $task*/
            $task = array_shift($tasks);
            try {
                $results[] = $task->perform();
            } catch (Exception $e) {
                $results[] = $e;
              /**
               * TODO: Add logging
               * We just don't want it to break at the moment.
               * Proper exception handling should take place
               * inside the perform method.
               **/
            }
        }
        return $results;
    }

    private function dedupe()
    {
        $result = [];
        $backTrack = [];
        while (count($this->tasks)) {
            $task = array_shift($this->tasks);
            $serializedTask = serialize(clone $task);
            if (array_search($serializedTask, $backTrack) === false) {
                $backTrack[] = $serializedTask;
                $result[] = $task;
            }
        }
        return $result;
    }
}
