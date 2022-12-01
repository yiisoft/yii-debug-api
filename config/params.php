<?php

declare(strict_types=1);

use Yiisoft\Yii\Debug\Api\Inspector\Command\CodeceptionCommand;
use Yiisoft\Yii\Debug\Api\Inspector\Command\PHPUnitCommand;
use Yiisoft\Yii\Debug\Api\Inspector\Command\PsalmCommand;

return [
    'yiisoft/yii-debug' => [
        'ignoredRequests' => [
            '/debug**',
            '/inspect**',
        ],
    ],
    'yiisoft/yii-debug-api' => [
        'enabled' => true,
        'allowedIPs' => ['127.0.0.1', '::1'],
        'allowedHosts' => [],
        'inspector' => [
            'commandMap' => [
                'tests' => [
                    PHPUnitCommand::COMMAND_NAME => PHPUnitCommand::class,
                    CodeceptionCommand::COMMAND_NAME => CodeceptionCommand::class,
                ],
                'analyse' => [
                    PsalmCommand::COMMAND_NAME => PsalmCommand::class,
                ],
            ],
        ],
    ],
    'yiisoft/yii-swagger' => [
        'annotation-paths' => [
            dirname(__DIR__) . '/src/Controller',
            dirname(__DIR__) . '/src/Middleware',
        ],
    ],
];
