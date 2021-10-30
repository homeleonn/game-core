<?php

use PHPUnit\Framework\TestCase;
use Homeleon\Http\Response;

class ResponseTest extends TestCase
{
    private Response $response;

    public function setUp(): void
    {
        $this->response = new Response;
    }

    public function testSetStatusCode()
    {
        $this->response->setStatusCode(404);

        $this->assertEquals(404, http_response_code());
    }

    public function testThatContentWasSet()
    {
        $this->response->setContent('hello world');

        $this->AssertEquals('hello world', $this->response->getContent());
    }
}
