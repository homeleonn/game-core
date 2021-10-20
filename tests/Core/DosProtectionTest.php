<?php

use PHPUnit\Framework\TestCase;
use Core\DosProtection\DosProtection;

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
        while ($this->limit--) {
            $this->dosProtection->isValid($this->ip);
        }

        $this->assertFalse($this->dosProtection->isValid($this->ip));
    }
}
