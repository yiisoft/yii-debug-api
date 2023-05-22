<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseFactoryInterface;
use Yiisoft\Csrf\CsrfMiddleware;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Validator\ValidatorInterface;
use Yiisoft\Yii\Debug\Api\Controller\CacheController;
use Yiisoft\Yii\Debug\Api\Controller\CommandController;
use Yiisoft\Yii\Debug\Api\Controller\ComposerController;
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
            Route::get('/view/{id}')
                ->action([DebugController::class, 'view'])
                ->name('view'),
            Route::get('/dump/{id}')
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
            Route::get('/files')
                ->action([InspectController::class, 'files'])
                ->name('files'),
            Route::get('/routes')
                ->action([InspectController::class, 'routes'])
                ->name('routes'),
            Route::get('/route/check')
                ->action([InspectController::class, 'checkRoute'])
                ->name('route/check'),
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
            Route::post('/curl/build')
                ->action([InspectController::class, 'buildCurl'])
                ->name('curl/build'),
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
                ),
            Route::get('/phpinfo')
                ->action([InspectController::class, 'phpinfo'])
                ->name('phpinfo'),
            Group::create('/command')
                ->namePrefix('command')
                ->routes(
                    Route::get('[/]')
                        ->action([CommandController::class, 'index'])
                        ->name('/index'),
                    Route::post('[/]')
                        ->action([CommandController::class, 'run'])
                        ->name('/run'),
                ),
            Group::create('/composer')
                ->namePrefix('composer')
                ->routes(
                    Route::get('[/]')
                        ->action([ComposerController::class, 'index'])
                        ->name('/index'),
                    Route::get('/inspect')
                        ->action([ComposerController::class, 'inspect'])
                        ->name('/inspect'),
                    Route::post('/require')
                        ->action([ComposerController::class, 'require'])
                        ->name('/require'),
                ),
            Group::create('/cache')
                ->namePrefix('cache')
                ->routes(
                    Route::get('[/]')
                        ->action([CacheController::class, 'view'])
                        ->name('/view'),
                    Route::delete('[/]')
                        ->action([CacheController::class, 'delete'])
                        ->name('/delete'),
                    Route::post('/clear')
                        ->action([CacheController::class, 'clear'])
                        ->name('/clear'),
                ),
        ),
];
