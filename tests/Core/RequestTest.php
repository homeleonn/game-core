<?php

use PHPUnit\Framework\TestCase;
use Homeleon\Http\Request;
use Homeleon\Validation\Validator;
use Homeleon\Contracts\Session\Session;

class RequestTest extends Testcase
{
    public Request $request;
    public array $server = [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/hello-world',
    ];
    public array $requestParams = [
        'page' => '22',
        'name' => 'test',
    ];

    private $validator;

    public function setUp(): void
    {
        $this->validator = $this->createMock(Validator::class);
        $this->session = $this->createMock(Session::class);
        $this->request = new Request($this->server, $this->requestParams, $this->session, $this->validator);
    }

    public function testGetRequestParam()
    {
        $key = 'page';
        $this->assertEquals($this->requestParams[$key], $this->request->get($key));
    }

    public function testThatRequestParamsWereSanitized()
    {
        $request = new Request($this->server, ["pa'ge" => '2"2<'], $this->session, $this->validator);
        $this->assertEquals('22', $request->get('page'));
    }

    public function testAll()
    {
        $this->AssertEquals(
            $this->requestParams,
            $this->request->all()
        );
    }

    public function testGetCorrectUri()
    {
        $this->assertEquals($this->server['REQUEST_URI'], $this->request->getUri());
    }


    public function testThatCorrectRequestMethodWasReturned()
    {
        $this->assertEquals($this->server['REQUEST_METHOD'], $this->request->getMethod());
    }

    public function testIsExceptedRequest()
    {
        $this->assertEquals(['name' => 'test'], $this->request->except(['page']));
    }

    public function testRequiredParamsAreReturned()
    {
        $this->assertEquals(['name' => 'test'], $this->request->only(['name']));
    }
}
