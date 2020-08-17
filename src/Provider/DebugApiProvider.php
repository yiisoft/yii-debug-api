<?php


namespace Yiisoft\Yii\Debug\Api\Provider;


use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Yii\Debug\Api\Controller\DebugController;
use Yiisoft\Yii\Debug\Api\Middleware\Debugger;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class DebugApiProvider extends ServiceProvider
{
    /**
     * @suppress PhanAccessMethodProtected
     * @param Container $container
     */
    public function register(Container $container): void
    {
        $routeCollector = $container->get(RouteCollectorInterface::class);
        $routeCollector->addGroup(Group::create('/debug', [
            Route::get('/', [DebugController::class, 'index'])->name('debug/index'),
            Route::get('/summary', [DebugController::class, 'summary'])->name('debug/summary'),
            Route::get('/view[/{id}]', [DebugController::class, 'view'])->name('debug/view')
        ])->addMiddleware(FormatDataResponseAsJson::class));

        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        $middlewareDispatcher->addMiddleware($container->get(Debugger::class));
    }
}
