<?php

namespace Core\DosProtection;

class DosProtection
{
    private $ips = [];
    private $limit; // limit connections per minute by ip

    public function __construct(int $limit = 60)
    {
        $this->limit = $limit;
    }

    public function isValid($ip)
    {
        if (!isset($this->ips[$ip])) {
            $this->ips[$ip] = [
                'count' => 1,
                'time'     => time()
            ];

            return true;
        }

        $this->ips[$ip]['count']++;

        if ($this->ips[$ip]['count'] >= $this->limit) {
            echo "DoS protection detected({$ip}): {$this->ips[$ip]['count']}\n";
        }

        return $this->ips[$ip]['count'] < $this->limit;
    }
}
