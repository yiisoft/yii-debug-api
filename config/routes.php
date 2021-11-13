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
use Yiisoft\Yii\Middleware\IpFilter;

if (!(bool)($params['yiisoft/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    Group::create('/debug')
        ->middleware(CorsMiddleware::class)
        ->middleware(
            static function (ResponseFactoryInterface $responseFactory) use ($params) {
                return new IpFilter(
                    Ip::rule()->ranges($params['yiisoft/yii-debug-api']['allowedIPs']),
                    $responseFactory
                );
            }
        )
        ->middleware(FormatDataResponseAsJson::class)
        ->middleware(ResponseDataWrapper::class)
        ->routes(
            Route::get('[/]')
                ->action([DebugController::class, 'index'])
                ->name('debug/index'),
            Route::get('/summary/{id}')
                ->action([DebugController::class, 'summary'])
                ->name('debug/summary'),
            Route::get('/view/{id}[/{collector}]')
                ->action([DebugController::class, 'view'])
                ->name('debug/view'),
            Route::get('/dump/{id}[/{collector}]')
                ->action([DebugController::class, 'dump'])
                ->name('debug/dump'),
            Route::get('/object/{id}/{objectId}')
                ->action([DebugController::class, 'object'])
                ->name('debug/object')
        ),
];
