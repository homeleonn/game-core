<?php

use PHPUnit\Framework\TestCase;
use Core\Helpers\Str;

class HelperStrTest extends TestCase
{
    public function testAddStartSlash()
    {
        $string = 'start slash was added';
        $this->assertSame('/' . $string, Str::addStartSlash($string));
        $this->assertSame('/' . $string, Str::addStartSlash('/' . $string));
    }
}
