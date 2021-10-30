<?php

use PHPUnit\Framework\TestCase;
use Homeleon\Support\Str;

class SupportStrTest extends TestCase
{
    public function testAddStartSlash()
    {
        $string = 'start slash was added';
        $this->assertSame('/' . $string, Str::addStartSlash($string));
        $this->assertSame('/' . $string, Str::addStartSlash('/' . $string));
    }

    public function testGeneratingRandomString()
    {
        $generatedString = Str::random(55);

        $this->assertTrue(is_string($generatedString));
        $this->AssertEquals(55, strlen($generatedString));
    }
}
