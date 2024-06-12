<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Provider;

use Psr\Container\ContainerInterface;
use Yiisoft\Di\ServiceProviderInterface;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Yii\Debug\Api\Debug\Http\DebugHttpApplicationWrapper;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\DebugHeaders;
use Yiisoft\Yii\Http\Application;

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
            RouteCollectorInterface::class => static function (
                ContainerInterface $container,
                RouteCollectorInterface $routeCollector
            ) {
                $routeCollector->prependMiddleware(DebugHeaders::class);
                return $routeCollector;
            },
            Application::class => static function (ContainerInterface $container, Application $application) {
                $applicationWrapper = $container->get(DebugHttpApplicationWrapper::class);
                $applicationWrapper->wrap($application);

                return $application;
            },
        ];
    }
}
