<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Debug\Middleware;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\DebugHeaders;
use Yiisoft\Yii\Debug\Debugger;
use Yiisoft\Yii\Debug\Storage\MemoryStorage;

final class DebugHeadersTest extends TestCase
{
    public function testHeaders(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            fn (string $route, array $parameters) => $route . '?' . http_build_query($parameters)
        );

        $debugger = new Debugger(new MemoryStorage(), []);
        $debugger->start(new stdClass());
        $expectedId = $debugger->getId();

        $middleware = new DebugHeaders($urlGenerator, $debugger);
        $response = $middleware->process(new ServerRequest(), $this->createRequestHandler());

        $this->assertSame($expectedId, $response->getHeaderLine('X-Debug-Id'));
        $this->assertSame('debug/api/view?id=' . $expectedId, $response->getHeaderLine('X-Debug-Link'));
    }

    protected function createRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle($request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }
}
