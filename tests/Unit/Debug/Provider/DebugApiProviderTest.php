<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Debug\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\DebugHeaders;
use Yiisoft\Yii\Debug\Api\Debug\Provider\DebugApiProvider;

final class DebugApiProviderTest extends TestCase
{
    public function testExtension(): void
    {
        $provider = new DebugApiProvider();

        $this->assertIsArray($provider->getDefinitions());
        $this->assertIsArray($provider->getExtensions());
        $this->assertEmpty($provider->getDefinitions());

        $extensions = $provider->getExtensions();
        $this->assertArrayHasKey(RouteCollectorInterface::class, $extensions);

        $routeCollectorDecorator = $extensions[RouteCollectorInterface::class];
        $this->assertIsCallable($routeCollectorDecorator);

        $routeCollector = $this->createMock(RouteCollectorInterface::class);
        $routeCollector->expects($this->once())
            ->method('prependMiddleware')
            ->with(DebugHeaders::class)
            ->willReturn($routeCollector);

        $this->assertSame($routeCollector, $routeCollectorDecorator($routeCollector));
    }

}
