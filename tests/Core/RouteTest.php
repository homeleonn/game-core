<?php

use PHPUnit\Framework\TestCase;
use Homeleon\Router\Route;

class RouteTest extends TestCase
{
    private Route $route;
    private string $id = '121';

    public function setUp(): void
    {
        // $this->route = new Route('get', '/user/{id}/{settings?}', function () {});
    }

    public function testRouteMatch()
    {
        $this->route     = new Route('get', '/user/{id}/{settings?}', function () {});

        $this->assertEquals('get', $this->route->getMethod());
        $this->assertTrue($this->route->match('get', "/user/{$this->id}"));

        return $this->route;
    }

    /**
     * @depends testRouteMatch
     */
    public function testPrepareMethodParameters($route)
    {
        $action = $route->getResolveAction();
        $this->assertEquals([$this->id], $route->getActualArguments());
    }
}
