<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Csrf\CsrfMiddleware;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\Debug\Api\Controller\DebugController;
use Yiisoft\Yii\Debug\Api\Controller\GitController;
use Yiisoft\Yii\Debug\Api\Controller\InspectController;
use Yiisoft\Yii\Debug\Api\Middleware\Cors;
use Yiisoft\Yii\Debug\Api\Middleware\ResponseDataWrapper;
use Yiisoft\Yii\Middleware\IpFilter;

if (!(bool) ($params['yiisoft/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    Group::create('/debug/api')
        ->withCors(Cors::class)
        ->disableMiddleware(CsrfMiddleware::class)
        ->middleware(
            static function (ResponseFactoryInterface $responseFactory, ValidatorInterface $validator) use ($params) {
                return new IpFilter(
                    validator: $validator,
                    responseFactory: $responseFactory,
                    ipRanges: $params['yiisoft/yii-debug-api']['allowedIPs']
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
    Group::create('/inspect/api')
        ->withCors(Cors::class)
        ->disableMiddleware(CsrfMiddleware::class)
        ->middleware(
            static function (ResponseFactoryInterface $responseFactory, ValidatorInterface $validator) use ($params) {
                return new IpFilter(
                    validator: $validator,
                    responseFactory: $responseFactory,
                    ipRanges: $params['yiisoft/yii-debug-api']['allowedIPs']
                );
            }
        )
        ->middleware(FormatDataResponseAsJson::class)
        ->middleware(ResponseDataWrapper::class)
        ->namePrefix('inspect/api/')
        ->routes(
            Route::get('/params')
                ->action([InspectController::class, 'params'])
                ->name('params'),
            Route::get('/config')
                ->action([InspectController::class, 'config'])
                ->name('config'),
            Route::get('/classes')
                ->action([InspectController::class, 'classes'])
                ->name('classes'),
            Route::get('/object')
                ->action([InspectController::class, 'object'])
                ->name('object'),
            Route::get('/command')
                ->action([InspectController::class, 'getCommands'])
                ->name('getCommands'),
            Route::post('/command')
                ->action([InspectController::class, 'runCommand'])
                ->name('runCommand'),
            Route::get('/files')
                ->action([InspectController::class, 'files'])
                ->name('files'),
            Route::get('/routes')
                ->action([InspectController::class, 'routes'])
                ->name('routes'),
            Route::get('/translations')
                ->action([InspectController::class, 'getTranslations'])
                ->name('getTranslations'),
            Route::put('/translations')
                ->action([InspectController::class, 'putTranslation'])
                ->name('putTranslation'),
            Route::get('/table')
                ->action([InspectController::class, 'getTables'])
                ->name('getTables'),
            Route::get('/table/{name}')
                ->action([InspectController::class, 'getTable'])
                ->name('getTable'),
            Route::put('/request')
                ->action([InspectController::class, 'request'])
                ->name('request'),
            Group::create('/git')
                ->namePrefix('/git')
                ->routes(
                    Route::get('/summary')
                        ->action([GitController::class, 'summary'])
                        ->name('summary'),
                    Route::post('/checkout')
                        ->action([GitController::class, 'checkout'])
                        ->name('checkout'),
                    Route::post('/command')
                        ->action([GitController::class, 'command'])
                        ->name('command'),
                    Route::get('/log')
                        ->action([GitController::class, 'log'])
                        ->name('log'),
                )
        ),
];
