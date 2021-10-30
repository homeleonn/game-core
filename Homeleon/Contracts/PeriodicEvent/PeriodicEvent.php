<?php

namespace Homeleon\Contracts\PeriodicEvent;

interface PeriodicEvent
{
    public function getTimeout(): int;
    public function execute(): void;
    public function addEvent($eventName, int $period, int|null $time = null): void;
}
