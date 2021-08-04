<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Provider;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Yii\Debug\Api\Middleware\DebugHeaders;
use Yiisoft\Router\RouteCollectorInterface;

class DebugApiProvider implements ServiceProviderInterface
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
            RouteCollectorInterface::class => static function (ContainerInterface $container, RouteCollectorInterface $routeCollector) {
                $routeCollector->prependMiddleware(DebugHeaders::class);
                return $routeCollector;
            }
        ];
    }
}
