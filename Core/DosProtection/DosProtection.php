<?php

namespace Core\DosProtection;

class DosProtection
{
    private $ips = [];
    private $limit; // limit connections per minute by ip
    private $timeDelay = 60;

    public function __construct(int $limit = 60)
    {
        $this->limit = $limit;
    }

    public function isValid($ip)
    {
        if (!isset($this->ips[$ip])) {
            $this->ips[$ip] = [
                'count' => 1,
                'time'  => time()
            ];
        } else {
            if ($this->ips[$ip]['time'] < time() - $this->timeDelay) {
                $this->ips[$ip]['count'] = 0;
            }
        }

        $this->ips[$ip]['count']++;
        $this->ips[$ip]['time'] = time();

        if ($this->ips[$ip]['count'] >= $this->limit) {
            echo "DoS protection detected({$ip}): {$this->ips[$ip]['count']}\n";
        }

        return $this->ips[$ip]['count'] < $this->limit;
    }
}
