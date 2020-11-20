<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Provider;

use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Yii\Debug\Api\Middleware\DebugHeaders;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class DebugApiProvider extends ServiceProvider
{
    /**
     * @suppress PhanAccessMethodProtected
     *
     * @param Container $container
     */
    public function register(Container $container): void
    {
        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        $middlewareDispatcher->addMiddleware($container->get(DebugHeaders::class));
    }
}
