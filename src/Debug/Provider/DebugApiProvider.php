<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Provider;

use Yiisoft\Di\ServiceProviderInterface;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\DebugHeaders;

final class DebugApiProvider implements ServiceProviderInterface
{
    /**
     * @psalm-suppress InaccessibleMethod
     */
    public function getDefinitions(): array
    {
        return [];
    }

    public function getExtensions(): array
    {
        return [
            RouteCollectorInterface::class => static function (RouteCollectorInterface $routeCollector) {
                $routeCollector->prependMiddleware(DebugHeaders::class);
                return $routeCollector;
            },
        ];
    }
}
