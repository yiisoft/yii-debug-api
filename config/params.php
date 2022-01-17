<?php

declare(strict_types=1);

return [
    'yiisoft/yii-debug' => [
        'optionalRequests' => ['/debug**'],
    ],
    'yiisoft/yii-debug-api' => [
        'enabled' => true,
        'allowedIPs' => ['127.0.0.1', '::1'],
        'allowedHosts' => [],
    ],
    'yiisoft/yii-swagger' => [
        'annotation-paths' => [
            dirname(__DIR__) . '/src/Controller',
            dirname(__DIR__) . '/src/Middleware',
        ],
    ],
];
