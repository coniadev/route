<?php

declare(strict_types=1);

namespace Conia\Route\Tests;

use Conia\Route\Dispatcher;
use Conia\Route\Route;
use Conia\Route\Tests\Fixtures\TestAfterAddText;
use Conia\Route\Tests\Fixtures\TestAfterRendererText;
use Conia\Route\Tests\Fixtures\TestBeforeFirst;
use Conia\Route\Tests\Fixtures\TestBeforeSecond;
use Conia\Route\Tests\Fixtures\TestMiddleware1;
use Conia\Route\Tests\Fixtures\TestMiddleware2;
use Conia\Route\Tests\Fixtures\TestMiddleware3;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DispatcherTest extends TestCase
{
    public function testDispatchClosure(): void
    {
        $route = new Route(
            '/',
            function () {
                $response = $this->responseFactory()->createResponse()->withHeader('Content-Type', 'text/html');
                $response->getBody()->write('Conia');

                return $response;
            }
        );
        $dispatcher = new Dispatcher();
        $response = $dispatcher->dispatch($this->request('GET', '/'), $route);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Conia', (string)$response->getBody());
    }

    public function testAddMiddleware(): void
    {
        $dispatcher = new Dispatcher();

        $dispatcher->middleware(new TestMiddleware1());
        $dispatcher->middleware(new TestMiddleware2());

        $this->assertSame(2, count($dispatcher->getMiddleware()));
    }

    public function testAddBeforeHandlers(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->before(new TestBeforeFirst())->before(new TestBeforeSecond());
        $handlers = $dispatcher->beforeHandlers();

        $this->assertSame(2, count($handlers));
        $this->assertInstanceof(TestBeforeFirst::class, $handlers[0]);
        $this->assertInstanceof(TestBeforeSecond::class, $handlers[1]);
    }

    public function testAddAfterHandlers(): void
    {
        $dispatcher = new Dispatcher();
        $dispatcher->after(new TestAfterRendererText($this->responseFactory()))->after(new TestAfterAddText());
        $handlers = $dispatcher->afterHandlers();

        $this->assertSame(2, count($handlers));
        $this->assertInstanceof(TestAfterRendererText::class, $handlers[0]);
        $this->assertInstanceof(TestAfterAddText::class, $handlers[1]);
    }

    public function testDispatchMiddlewareApplied(): void
    {
        $route = (new Route(
            '/',
            function (Request $request) {
                $response = $this->responseFactory()->createResponse()->withHeader('Content-Type', 'text/html');
                $response->getBody()->write(
                    $request->getAttribute('mw1') .
                    '|' . $request->getAttribute('mw2') .
                    '|' . $request->getAttribute('mw3')
                );

                return $response;
            }
        ))->middleware(new TestMiddleware2())->middleware(new TestMiddleware3());
        $dispatcher = new Dispatcher();
        $dispatcher->middleware(new TestMiddleware1());
        $response = $dispatcher->dispatch($this->request('GET', '/'), $route);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Middleware 1|Middleware 2 - After 1|Middleware 3 - After 2', (string)$response->getBody());
    }
}
