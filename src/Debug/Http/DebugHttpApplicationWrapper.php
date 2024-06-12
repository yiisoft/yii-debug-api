<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Http;

use Closure;
use Yiisoft\Yii\Debug\Api\Debug\Middleware\DebugFallbackHandler;
use Yiisoft\Yii\Http\Application;

final readonly class DebugHttpApplicationWrapper
{
    public function __construct(
        private DebugFallbackHandler $debugFallbackHandler
    ) {
    }

    public function wrap(Application $application): void
    {
        $debugFallbackHandler = $this->debugFallbackHandler;
        $closure = Closure::bind(static function (Application $application) use ($debugFallbackHandler) {
            $application->fallbackHandler = $debugFallbackHandler->withFallbackRequestHandler($application->fallbackHandler);
        }, null, $application);

        $closure($application);
    }
}
