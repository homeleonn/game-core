<?php

namespace Homeleon\Socket;

use Homeleon\Contracts\PeriodicEvent\PeriodicEvent;

class PeriodicEventWorker implements PeriodicEvent
{
    private array $events = [];
    private int|null $timeout = null;
    private $executer;

    public function __construct($executer)
    {
        $this->executer = $executer;
    }

    public function getTimeout(): int
    {
        return $this->timeout - time();
    }

    public function execute(): void
    {
        if (empty($this->events)) return;

        $time = time();
        $executed = false;

        foreach ($this->events as &$event) {
            if ($event['time'] <= $time) {
                $this->executer->periodicEvent($event['name']);
                $this->resetEventTime($event, $time);
                $executed = true;
            }
        }

        if ($executed) {
            $this->calibrationTimer();
        }
    }

    public function calibrationTimer()
    {
        $this->timeout = $this->events[0]['time'];
        foreach ($this->events as $event) {
            if ($event['time'] < $this->timeout) {
                $this->timeout = $event['time'];
            }
        }
    }

    public function addEvent($eventName, int $period, int|null $time = null): void
    {
        $event['name'] = $eventName;
        $event['time'] = $time ?? time();
        $event['time'] += $period;

        $this->updateTimeout($event);

        $event['period'] = $period;
        $this->events[] = $event;
    }

    public function updateTimeout($event)
    {
        if (is_null($this->timeout) || $event['time'] < $this->timeout) {
            $this->timeout = $event['time'];
        }
    }

    private function resetEventTime(&$event, $time)
    {
        $event['time'] = $time + $event['period'];
    }
}
