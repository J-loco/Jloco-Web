<?php

declare(strict_types=1);

namespace StarLoco\Web\Tests\Unit\Http;

use FastRoute\Dispatcher;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use StarLoco\Web\Controller\LadderController;
use StarLoco\Web\Http\LegacyUrls;
use StarLoco\Web\Http\Request;
use StarLoco\Web\Http\Router;
use StarLoco\Web\Tests\Support\TestConfig;

final class RoutingTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router(TestConfig::make());
    }

    public function testGeneratesUrlsWithPlaceholdersAndQuery(): void
    {
        self::assertSame('http://localhost/dofus/', $this->router->url('home'));
        self::assertSame('http://localhost/dofus/?p=2', $this->router->url('home', ['p' => 2]));
        self::assertSame('http://localhost/dofus/shop/1/items/8949', $this->router->url('shop_item', ['server' => 1, 'template' => 8949]));
        self::assertSame('http://localhost/dofus/drops?by=item&q=laine+de+bouftou', $this->router->url('drops', ['by' => 'item', 'q' => 'laine de bouftou']));
        self::assertSame('http://localhost/dofus/', $this->router->url('home', ['p' => null, 'q' => '']), 'empty values are dropped');
    }

    public function testPlaceholderRegexesMayContainBraces(): void
    {
        // Regression: the emailed reset link used to end with "}" after each placeholder.
        $selector = str_repeat('a', 24);
        $token = str_repeat('b', 64);
        $url = $this->router->url('password_reset_confirm', ['selector' => $selector, 'token' => $token]);

        self::assertSame("http://localhost/dofus/password/reset/$selector/$token", $url);
        self::assertSame(Dispatcher::FOUND, $this->router->match('GET', "/password/reset/$selector/$token")[0], 'generated URLs match their route');
    }

    public function testMissingPlaceholderIsAnError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->router->url('shop_item', ['server' => 1]);
    }

    public function testUnknownRouteIsAnError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->router->url('nope');
    }

    public function testMatching(): void
    {
        $found = $this->router->match('GET', '/ladder/jobs/24');
        self::assertSame(Dispatcher::FOUND, $found[0]);
        self::assertSame([LadderController::class, 'job'], ($found[1] ?? self::fail('route'))->handler);
        self::assertSame(['job' => '24'], $found[2] ?? null);

        self::assertSame(Dispatcher::FOUND, $this->router->match('HEAD', '/ladder')[0], 'HEAD is answered like GET');
        self::assertSame(Dispatcher::NOT_FOUND, $this->router->match('GET', '/ladder/jobs/abc')[0]);
        self::assertSame(Dispatcher::METHOD_NOT_ALLOWED, $this->router->match('GET', '/logout')[0]);
        self::assertFalse(($this->router->match('POST', '/account')[1] ?? self::fail('route'))->csrf, 'Dedipass callback has no CSRF token');
        self::assertTrue(($this->router->match('POST', '/account/password')[1] ?? self::fail('route'))->csrf);
    }

    /** @return iterable<string, array{array<string, string>, ?string}> */
    public static function legacyUrls(): iterable
    {
        yield 'home' => [['page' => 'index'], 'http://localhost/dofus/'];
        yield 'ladder' => [['page' => 'ladder'], 'http://localhost/dofus/ladder'];
        yield 'terms (cgu)' => [['page' => 'cgu'], 'http://localhost/dofus/terms'];
        yield 'shop category' => [['page' => 'shop', 'server' => '1', 'category' => '12'], 'http://localhost/dofus/shop/1/12'];
        yield 'shop server' => [['page' => 'shop', 'server' => '1'], 'http://localhost/dofus/shop/1'];
        yield 'buy' => [['page' => 'buy', 'server' => '1', 'template' => '8949'], 'http://localhost/dofus/shop/1/items/8949'];
        yield 'news pagination' => [['num' => '3'], 'http://localhost/dofus/?p=3'];
        yield 'unknown page' => [['page' => '../launcher/status'], null];
        yield 'not legacy' => [['p' => '2'], null];
    }

    /** @param array<string, string> $query */
    #[DataProvider('legacyUrls')]
    public function testLegacyRedirects(array $query, ?string $expected): void
    {
        $request = new Request('GET', '/', $query, [], [], []);
        self::assertSame($expected, new LegacyUrls($this->router)->redirectFor($request));
    }

    public function testRequestPathStripsTheBasePath(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER['REQUEST_URI'] = '/dofus/ladder/pvp/?x=1';
            $_SERVER['REQUEST_METHOD'] = 'get';
            $request = Request::fromGlobals('/dofus');
            self::assertSame('/ladder/pvp', $request->path);
            self::assertSame('GET', $request->method);

            $_SERVER['REQUEST_URI'] = '/dofus/index.php?page=ladder';
            self::assertSame('/', Request::fromGlobals('/dofus')->path);
        } finally {
            $_SERVER = $server;
        }
    }

    public function testClientIpOnlyTrustsCloudflareWhenConfigured(): void
    {
        $request = new Request('GET', '/', [], [], [], ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_CF_CONNECTING_IP' => '203.0.113.9']);
        self::assertSame('10.0.0.1', $request->clientIp(false));
        self::assertSame('203.0.113.9', $request->clientIp(true));
    }

    public function testInputIsScrubbedAndTyped(): void
    {
        $request = new Request('POST', '/', ['q' => ['array']], ['name' => "caf\xE9"], [], []);
        self::assertSame('caf?', $request->input('name'));
        self::assertSame('', $request->query('q'), 'arrays are not strings');
        self::assertTrue($request->has('name'));
    }
}
