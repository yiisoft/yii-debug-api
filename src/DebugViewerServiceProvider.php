<?php

namespace Yiisoft\Yii\Debug\Viewer;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Yii\Debug\Viewer\Middleware\TagHeader;
use Yiisoft\Yii\Debug\Viewer\Middleware\Toolbar;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class DebugViewerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $middleware = $container->get(MiddlewareDispatcher::class);
        $middleware->addMiddleware($container->get(TagHeader::class));
        $middleware->addMiddleware($container->get(Toolbar::class));
    }
}
