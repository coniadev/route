<?php

declare(strict_types=1);

namespace Conia\Route\Tests;

use Conia\Route\Exception\MethodNotAllowedException;
use Conia\Route\Exception\NotFoundException;
use Conia\Route\Exception\RuntimeException;
use Conia\Route\Group;
use Conia\Route\Route;
use Conia\Route\Router;
use Conia\Route\Tests\Fixtures\TestController;
use Conia\Route\Tests\Fixtures\TestEndpoint;
use PHPUnit\Framework\Attributes\TestDox;

class RouterTest extends TestCase
{
    public function testMatching(): void
    {
        $router = new Router();
        $index = new Route('/', fn () => null, 'index');
        $router->addRoute($index);
        $albums = new Route('/albums', fn () => null);
        $router->addRoute($albums);
        $router->addGroup(new Group('/albums', function (Group $group) {
            $ctrl = TestController::class;
            $group->addRoute(Route::get('/{name}', "{$ctrl}::albumName"));
        }));

        $this->assertEquals('index', $router->match($this->request('GET', ''))->name());

        $this->assertEquals($index, $router->match($this->request('GET', '')));
        $this->assertEquals($albums, $router->match($this->request('GET', '/albums')));
        $this->assertEquals($albums, $router->match($this->request('GET', '/albums?q=Symbolic')));
        $this->assertEquals('', $router->match($this->request('GET', '/albums/name'))->name());
    }

    public function testThrowingNotFoundException(): void
    {
        $this->throws(NotFoundException::class);

        $router = new Router();
        $router->match($this->request('GET', '/does-not-exist'));
    }

    public function testSimpleMatchingUrlEncoded(): void
    {
        $router = new Router();
        $route = new Route('/album name/...slug', fn () => null, 'encoded');
        $router->addRoute($route);

        $this->assertEquals('encoded', $router->match(
            $this->request('GET', '/album%20name/scream%20bloody%20gore')
        )->name());
        $this->assertEquals('scream bloody gore', $route->args()['slug']);
    }

    public function testMatchingWithHelpers(): void
    {
        $this->throws(MethodNotAllowedException::class);

        $router = new Router();
        $index = $router->get('/', fn () => null, 'index');
        $albums = $router->post('/albums', fn () => null);

        $this->assertEquals('index', $router->match($this->request('GET', ''))->name());
        $this->assertEquals('', $router->match($this->request('POST', '/albums'))->name());
        $this->assertEquals($index, $router->match($this->request('GET', '')));
        $this->assertEquals($albums, $router->match($this->request('POST', '/albums')));

        $router->match($this->request('GET', '/albums'));
    }

    public function testGenerateRouteUrl(): void
    {
        $router = new Router();
        $albums = new Route('albums/{from}/{to}', fn () => null, 'albums');
        $router->addRoute($albums);

        $this->assertEquals('/albums/1990/1995', $router->routeUrl('albums', from: 1990, to: 1995));
        $this->assertEquals('/albums/1988/1991', $router->routeUrl('albums', ['from' => 1988, 'to' => 1991]));
    }

    public function testFailToGenerateRouteUrl(): void
    {
        $this->throws(RuntimeException::class, 'Route not found');

        $router = new Router();
        $router->routeUrl('fantasy');
    }

    public function testStaticRoutesUnnamed(): void
    {
        $router = new Router();
        $router->addStatic('/static', $this->root . '/public/static');

        $this->assertEquals('/static/test.json', $router->staticUrl('/static', 'test.json'));
        $this->assertMatchesRegularExpression('/\?v=[a-f0-9]{8}$/', $router->staticUrl('/static', 'test.json', true));
        $this->assertMatchesRegularExpression('/\?exists=true&v=[a-f0-9]{8}$/', $router->staticUrl('/static', 'test.json?exists=true', true));
        $this->assertMatchesRegularExpression(
            '/https:\/\/conia.local\/static\/test.json\?v=[a-f0-9]{8}$/',
            $router->staticUrl(
                '/static',
                'test.json',
                host: 'https://conia.local/',
                bust: true,
            )
        );
    }

    public function testStaticRoutesNamed(): void
    {
        $router = new Router();
        $router->addStatic('/static', $this->root . '/public/static', 'staticroute');

        $this->assertEquals('/static/test.json', $router->staticUrl('staticroute', 'test.json'));
    }

    public function testStaticRoutesToNonexistentDirectory(): void
    {
        $this->throws(RuntimeException::class, 'does not exist');

        (new Router())->addStatic('/static', $this->root . '/fantasy/dir');
    }

    public function testNonExistingFilesNoCachebuster(): void
    {
        $router = new Router();
        $router->addStatic('/static', $this->root . '/public/static');

        // Non existing files should not have a cachebuster attached
        $this->assertMatchesRegularExpression('/https:\/\/conia.local\/static\/does-not-exist.json$/', $router->staticUrl(
            '/static',
            'does-not-exist.json',
            host: 'https://conia.local/',
            bust: true,
        ));
    }

    public function testStaticRouteDuplicateNamed(): void
    {
        $this->throws(RuntimeException::class, 'Duplicate static route: static');

        $router = new Router();
        $router->addStatic('/static', $this->root . '/public/static', 'static');
        $router->addStatic('/anotherstatic', $this->root . '/public/static', 'static');
    }

    public function testStaticRouteDuplicateUnnamed(): void
    {
        $this->throws(RuntimeException::class, 'Duplicate static route: /static');

        $router = new Router();
        $router->addStatic('/static', $this->root . '/public/static');
        $router->addStatic('/static', $this->root . '/public/static');
    }

    #[TestDox("GET matching")]
    public function testGETMatching(): void
    {
        $router = new Router();
        $route = Route::get('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('GET', '/')));
    }

    #[TestDox("HEAD matching")]
    public function testHEADMatching(): void
    {
        $router = new Router();
        $route = Route::head('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('HEAD', '/')));
    }

    #[TestDox("PUT matching")]
    public function testPUTMatching(): void
    {
        $router = new Router();
        $route = Route::put('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('PUT', '/')));
    }

    #[TestDox("POST matching")]
    public function testPOSTMatching(): void
    {
        $router = new Router();
        $route = Route::post('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('POST', '/')));
    }

    #[TestDox("PATCH matching")]
    public function testPATCHMatching(): void
    {
        $router = new Router();
        $route = Route::patch('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('PATCH', '/')));
    }

    #[TestDox("DELETE matching")]
    public function testDELETEMatching(): void
    {
        $router = new Router();
        $route = Route::delete('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('DELETE', '/')));
    }

    #[TestDox("OPTIONS matching")]
    public function testOPTIONSMatching(): void
    {
        $router = new Router();
        $route = Route::options('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('OPTIONS', '/')));
    }

    public function testMatchingWrongMethod(): void
    {
        $this->throws(MethodNotAllowedException::class);

        $router = new Router();
        $route = Route::get('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('POST', '/')));
    }

    #[TestDox("Multiple methods matching I")]
    public function testMultipleMethodsMatchingI(): void
    {
        $this->throws(MethodNotAllowedException::class);

        $router = new Router();
        $route = Route::get('/', fn () => null)->method('post');
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('GET', '/')));
        $this->assertEquals($route, $router->match($this->request('POST', '/')));
        $router->match($this->request('PUT', '/'));
    }

    #[TestDox("Multiple methods matching II")]
    public function testMultipleMethodsMatchingII(): void
    {
        $this->throws(MethodNotAllowedException::class);

        $router = new Router();
        $route = (new Route('/', fn () => null))->method('gEt', 'Put');
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('GET', '/')));
        $this->assertEquals($route, $router->match($this->request('PUT', '/')));
        $router->match($this->request('POST', '/'));
    }

    #[TestDox("Multiple methods matching III")]
    public function testMultipleMethodsMatchingIII(): void
    {
        $this->throws(MethodNotAllowedException::class);

        $router = new Router();
        $route = (new Route('/', fn () => null))->method('get')->method('head');
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('GET', '/')));
        $this->assertEquals($route, $router->match($this->request('HEAD', '/')));
        $router->match($this->request('POST', '/'));
    }

    public function testAllMethodsMatching(): void
    {
        $router = new Router();
        $route = new Route('/', fn () => null);
        $router->addRoute($route);

        $this->assertEquals($route, $router->match($this->request('GET', '/')));
        $this->assertEquals($route, $router->match($this->request('HEAD', '/')));
        $this->assertEquals($route, $router->match($this->request('POST', '/')));
        $this->assertEquals($route, $router->match($this->request('PUT', '/')));
        $this->assertEquals($route, $router->match($this->request('PATCH', '/')));
        $this->assertEquals($route, $router->match($this->request('DELETE', '/')));
        $this->assertEquals($route, $router->match($this->request('OPTIONS', '/')));
    }

    public function testSamePatternMultipleMethods(): void
    {
        $this->throws(MethodNotAllowedException::class);

        $router = new Router();
        $puthead = (new Route('/', fn () => null, 'puthead'))->method('HEAD', 'Put');
        $router->addRoute($puthead);
        $get = (new Route('/', fn () => null, 'get'))->method('GET');
        $router->addRoute($get);

        $this->assertEquals($get, $router->match($this->request('GET', '/')));
        $this->assertEquals($puthead, $router->match($this->request('PUT', '/')));
        $this->assertEquals($puthead, $router->match($this->request('HEAD', '/')));
        $router->match($this->request('POST', '/'));
    }

    public function testAddEndpoint(): void
    {
        $router = new Router();
        $router->endpoint('/endpoints', TestEndpoint::class, ['id', 'category'])->add();

        $requestRoute = $router->match($this->request('POST', '/endpoints'));
        $this->assertEquals('/endpoints', $requestRoute->pattern());
        $this->assertEquals([TestEndpoint::class, 'post'], $requestRoute->view());
        $this->assertEquals([], $requestRoute->args());
    }

    public function testAddRoutesWithCallback(): void
    {
        $router = new Router();
        $router->routes(function (Router $r): void {
            $r->get('/', fn () => null, 'index');
            $r->post('/albums', fn () => null);
        });

        $this->assertEquals('index', $router->match($this->request('GET', ''))->name());
        $this->assertEquals('', $router->match($this->request('POST', '/albums'))->name());
    }
}
