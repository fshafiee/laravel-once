<?php


namespace LaravelOnce\Support;

use Carbon\Carbon;

class DebounceWait
{
    /**
     * @var int
     */
    private $startTime;
    /**
     * @var int
     */
    private $waitTime;

    /**
     * CacheTtl constructor.
     *
     * @param int    $startTime start time timestamp
     * @param int    $waitTime wait time before actually process the task in seconds
     */
    public function __construct(int $startTime, int $waitTime)
    {
        $this->startTime = $startTime;
        $this->waitTime = $waitTime;
    }

    public function getRemainingWaitTime(): int
    {
        $remaining = Carbon::now()->timestamp - ($this->startTime + $this->waitTime);
        return $remaining < 0 ?  abs($remaining) : 0;
    }

    public static function fromString(string $json): self
    {
        $obj = json_decode($json);
        return new self($obj->s, $obj->w);
    }
    public function __toString(): string
    {
        return json_encode([
            's' => $this->startTime,
            'w' => $this->waitTime,
        ]);
    }

}