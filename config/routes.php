<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseFactoryInterface;
use Tuupola\Middleware\CorsMiddleware;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Validator\Rule\Ip;
use Yiisoft\Yii\Debug\Api\Controller\DebugController;
use Yiisoft\Yii\Debug\Api\Middleware\ResponseDataWrapper;
use Yiisoft\Yii\Web\Middleware\IpFilter;

if (!(bool)($params['yiisoft/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    Group::create(
        '/debug',
        [
            Route::get('[/]', [DebugController::class, 'index'])->name('debug/index'),
            Route::get('/summary/{id}', [DebugController::class, 'summary'])->name('debug/summary'),
            Route::get('/view/{id}[/{collector}]', [DebugController::class, 'view'])->name('debug/view'),
            Route::get('/object/{id}[/{collector}]', [DebugController::class, 'object'])->name('debug/object'),
        ]
    )
        ->addMiddleware(CorsMiddleware::class)
        ->addMiddleware(ResponseDataWrapper::class)
        ->addMiddleware(FormatDataResponseAsJson::class)
        ->addMiddleware(
            static function (ResponseFactoryInterface $responseFactory) use ($params) {
                return new IpFilter(
                    (new Ip())->ranges($params['yiisoft/yii-debug-api']['allowedIPs']),
                    $responseFactory
                );
            }
        )
];
