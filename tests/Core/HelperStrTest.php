<?php

use PHPUnit\Framework\TestCase;
use Homeleon\Support\Str;

class HelperStrTest extends TestCase
{
    public function testAddStartSlash()
    {
        $string = 'start slash was added';
        $this->assertSame('/' . $string, Str::addStartSlash($string));
        $this->assertSame('/' . $string, Str::addStartSlash('/' . $string));
    }
}
