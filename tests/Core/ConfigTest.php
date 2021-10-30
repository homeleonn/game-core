<?php

use PHPUnit\Framework\TestCase;
use Homeleon\Config\Config;

class ConfigTest extends TestCase
{
    protected Config $config;

    public function setUp(): void
    {
        $this->config = new Config([
            'test' => 'fake'
        ]);
    }

    public function testThatConfigIsReturned()
    {
        $this->assertEquals('fake', $this->config->get('test'));
    }

    public function testThatConfigIsReturnsNull()
    {
        $this->assertEquals(null, $this->config->get('wrongKey'));
    }
}
