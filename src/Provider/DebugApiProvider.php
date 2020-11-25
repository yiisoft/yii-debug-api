<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Provider;

use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Yii\Debug\Api\Middleware\DebugHeaders;
use Yiisoft\Router\RouteCollectorInterface;

class DebugApiProvider extends ServiceProvider
{
    /**
     * @suppress PhanAccessMethodProtected
     *
     * @param Container $container
     */
    public function register(Container $container): void
    {
        $routeCollector = $container->get(RouteCollectorInterface::class);
        $routeCollector->addMiddleware(DebugHeaders::class);
    }
}
