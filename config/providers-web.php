<?php

declare(strict_types=1);

use Yiisoft\Yii\Debug\Api\Provider\DebugApiProvider;

if (!(bool)($params['yiisoft/yii-debug-api']['enabled'] ?? false)) {
    return [];
}

return [
    'yiisoft/yii-debug-api' => [
        '__class' => DebugApiProvider::class,
        '__construct()' => [$params['yiisoft/yii-debug-api']]
    ]
];
