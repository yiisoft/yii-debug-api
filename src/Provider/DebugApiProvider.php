<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Provider;

use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Di\Container;
use Yiisoft\Di\Support\ServiceProvider;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Validator\Rule\Ip;
use Yiisoft\Yii\Debug\Api\Controller\DebugController;
use Yiisoft\Yii\Debug\Api\Middleware\Debugger;
use Yiisoft\Yii\Debug\Api\Middleware\ResponseDataWrapper;
use Yiisoft\Yii\Web\Middleware\IpFilter;
use Yiisoft\Yii\Web\MiddlewareDispatcher;

class DebugApiProvider extends ServiceProvider
{
    private array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    /**
     * @suppress PhanAccessMethodProtected
     * @param Container $container
     */
    public function register(Container $container): void
    {
        $routeCollector = $container->get(RouteCollectorInterface::class);
        $allowedIPs = $this->params['allowedIPs'];
        $routeCollector->addGroup(
            Group::create(
                '/debug',
                [
                    Route::get('[/]', [DebugController::class, 'index'])->name('debug/index'),
                    Route::get('/summary[/{id}]', [DebugController::class, 'summary'])->name('debug/summary'),
                    Route::get('/view[/{id}[/{collector}]]', [DebugController::class, 'view'])->name('debug/view')
                ]
            )
                ->addMiddleware(ResponseDataWrapper::class)
                ->addMiddleware(FormatDataResponseAsJson::class)
                ->addMiddleware(
                    static function () use ($container, $allowedIPs) {
                        return new IpFilter(
                            (new Ip())->ranges($allowedIPs),
                            $container->get(ResponseFactoryInterface::class)
                        );
                    }
                )
        );

        $middlewareDispatcher = $container->get(MiddlewareDispatcher::class);
        $middlewareDispatcher->addMiddleware($container->get(Debugger::class));
    }
}
