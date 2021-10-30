<?php

use PHPUnit\Framework\TestCase;
use Homeleon\DosProtection\DosProtection;

class DosProtectionTest extends TestCase
{
    protected DosProtection $dosProtection;
    protected string $ip = '127.0.0.1';
    protected int $limit = 10;

    public function setUp(): void
    {
        $this->dosProtection = new DosProtection($this->limit);
    }

    public function testThatLimitIsNotExceeded()
    {
        $this->assertTrue($this->dosProtection->isValid($this->ip));
    }

    public function testThatLimitIsExceeded()
    {
        ob_start();
        $this->ticks($this->limit);
        $isValid = $this->dosProtection->isValid($this->ip);
        ob_end_clean();

        $this->assertFalse($isValid);
    }

    public function testThatIpWasCleared()
    {
        $twoThirdsOfLimit = round($this->limit / 1.5);
        $this->ticks($twoThirdsOfLimit);
        $this->dosProtection->clearIp($this->ip);
        $this->ticks($twoThirdsOfLimit);

        $this->assertTrue($this->dosProtection->isValid($this->ip));

    }

    private function ticks(int $count): void
    {
        if ($count < 0) {
            throw new UnexpectedValueException('Count of ticks in less than 0');
        }

        while ($count--) {
            $this->dosProtection->isValid($this->ip);
        }
    }
}
