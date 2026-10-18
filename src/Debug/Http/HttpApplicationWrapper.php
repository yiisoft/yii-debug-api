<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Http;

use Closure;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\MiddlewareDispatcherMiddleware;
use Yiisoft\Yii\Http\Application;

final class HttpApplicationWrapper
{
    public function __construct(
        private MiddlewareDispatcher $middlewareDispatcher,
        private array $middlewareDefinitions,
    ) {}

    public function wrap(Application $application): Application
    {
        $middlewareDispatcher = $this->middlewareDispatcher;
        $middlewareDefinitions = $this->middlewareDefinitions;

        /**
         * `Application` properties are readonly, so create a new instance with a wrapped dispatcher.
         *
         * @psalm-suppress InaccessibleProperty
         */
        $closure = Closure::bind(
            static fn(Application $application): Application => new Application(
                $middlewareDispatcher->withMiddlewares([
                    ...$middlewareDefinitions,
                    ['class' => MiddlewareDispatcherMiddleware::class, '$middlewareDispatcher' => $application->dispatcher],
                ]),
                $application->eventDispatcher,
                $application->fallbackHandler,
            ),
            null,
            Application::class,
        );

        return $closure($application);
    }
}
