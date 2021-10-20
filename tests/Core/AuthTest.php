<?php

use PHPUnit\Framework\TestCase;
use Core\Auth\Auth;

class AuthTest extends TestCase
{
    public function testCheck()
    {
        $stub = $this->createMock(Auth::class);
        $stub->method('check')
            ->willReturn(1);

        $this->assertSame(1, $stub->check());
    }
}
