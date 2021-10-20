<?php

use PHPUnit\Framework\TestCase;
use Core\Config\Config;

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

    public function testThatConfigIsThrowException()
    {
        $this->expectException(Exception::class);
        $this->config->get('wrongKey');
    }
}
