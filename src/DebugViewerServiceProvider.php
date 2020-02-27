<?php

namespace Yiisoft\Yii\Debug\Viewer;

use Yiisoft\Di\Container;
use Yiisoft\Di\Contracts\ServiceProviderInterface;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Yii\Debug\Viewer\Controllers\ToolbarController;
use Yiisoft\Yii\Debug\Viewer\Middleware\DebugHeaders;
use Yiisoft\Yii\Debug\Viewer\Middleware\Toolbar;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class DebugViewerServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $router = $container->get(RouteCollectorInterface::class);
        $router->addGroup(
            Group::create(
                '/_dbg/',
                [
                    Route::get('view/{id}', [ToolbarController::class, 'view'])->name('debugger.view'),
                ]
            )
        );
        $middleware = $container->get(MiddlewareDispatcher::class);
        $middleware->addMiddleware($container->get(DebugHeaders::class));
        $middleware->addMiddleware($container->get(Toolbar::class));
    }
}
