<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseFactoryInterface;
use Tuupola\Middleware\CorsMiddleware;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\Debug\Api\Controller\DebugController;
use Yiisoft\Yii\Debug\Api\Middleware\ResponseDataWrapper;
use Yiisoft\Yii\Middleware\IpFilter;

if (!(bool)($params['yiisoft/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    Group::create('/debug/api')
        ->withCors(CorsMiddleware::class)
        ->middleware(
            static function (ResponseFactoryInterface $responseFactory, ValidatorInterface $validator) use ($params) {
                return new IpFilter(
                    $validator,
                    $responseFactory,
                    null,
                    $params['yiisoft/yii-debug-api']['allowedIPs']
                );
            }
        )
        ->middleware(FormatDataResponseAsJson::class)
        ->middleware(ResponseDataWrapper::class)
        ->namePrefix('debug/api/')
        ->routes(
            Route::get('[/]')
                ->action([DebugController::class, 'index'])
                ->name('index'),
            Route::get('/summary/{id}')
                ->action([DebugController::class, 'summary'])
                ->name('summary'),
            Route::get('/view/{id}[/[{collector}]]')
                ->action([DebugController::class, 'view'])
                ->name('view'),
            Route::get('/dump/{id}[/[{collector}]]')
                ->action([DebugController::class, 'dump'])
                ->name('dump'),
            Route::get('/object/{id}/{objectId}')
                ->action([DebugController::class, 'object'])
                ->name('object')
        ),
];
