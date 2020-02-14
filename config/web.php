<?php

return (bool)($params['debug.enabled'] ?? true) ? [
    'app' => [
        'bootstrap' => ['debug' => 'debug'],
        'modules' => [
            'debug' => array_filter([
                '__class' => \Yiisoft\Yii\Debug\Module::class,
                'allowedIPs' => $params['debug.allowedIPs'],
                'panels' => [
                    'config' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\ConfigPanel::class,
                    ],
                    'request' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\RequestPanel::class,
                    ],
                    'log' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\LogPanel::class,
                    ],
                    'profiling' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\ProfilingPanel::class,
                    ],
                    'db' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\DbPanel::class,
                    ],
                    'event' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\EventPanel::class,
                    ],
                    'assets' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\AssetPanel::class,
                    ],
                    'mail' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\MailPanel::class,
                    ],
                    'timeline' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\TimelinePanel::class,
                    ],
                    'user' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\UserPanel::class,
                    ],
                    'router' => [
                        '__class' => \Yiisoft\Yii\Debug\Viewer\Panels\RouterPanel::class,
                    ],
                ],
            ]),
        ],
    ],
] : [];
