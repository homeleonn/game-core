<?php

namespace Homeleon\DosProtection;

use Homeleon\Contracts\DosProtection\DosProtectionInterface;

class DosProtection implements DosProtectionInterface
{
    private array $ips = [];
    private string $ip;
    private int|float $time;

    /**
     * @param $limit max num of touch program during $timeDelay
     * @param $timeDelay seconds, after which dos counter will be reset
     */
    public function __construct(
        private int $limit = 100,
        private int $timeDelay = 5,
    ) {}

    /**
     * Check that current tick is not dos
     *
     * @param  string $ip current ip
     * @return bool
     */
    public function isValid(string $ip): bool
    {
        $this->ip = $ip;
        $this->time = time();

        $this->handle();

        if ($this->isDos()) {
            echo "DoS protection detected({$this->ip}): {$this->ips[$this->ip]['count']}\n";
        }

        return !$this->isDos();
    }

    /**
     * clear ip from container
     *
     * @param  string $ip ip which need to remove
     * @return void
     */
    public function clearIp(string $ip): void
    {
        if (isset($this->ips[$ip])) {
            unset($this->ips[$ip]);
        }
    }

    /**
     * Handle the tick. Initialize a touch and/or increment it
     *
     * @return void
     */
    private function handle(): void
    {
        if (!isset($this->ips[$this->ip]) || $this->isToReset()) {
            $this->ips[$this->ip]['count'] = 0;
        }

        $this->tick();
    }

    /**
     * Is touches are bigger than limit
     *
     * @return bool
     */
    private function isDos(): bool
    {
        return $this->ips[$this->ip]['count'] >= $this->limit;
    }

    /**
     * Is last touch was bigger than time delay ago
     *
     * @return bool
     */
    private function isToReset(): bool
    {
        return $this->time - $this->timeDelay > $this->ips[$this->ip]['time'];
    }

    /**
     * Tick ip. Increment touches and mainstreaming the time of touch
     *
     * @return void
     */
    private function tick(): void
    {
        $this->ips[$this->ip]['count']++;
        $this->ips[$this->ip]['time'] = $this->time;
    }
}
