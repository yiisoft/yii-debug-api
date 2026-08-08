<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/config', isDev: false)
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // Optional integrations for custom debug panels/collectors: consumers only need these installed if they use
    // the corresponding panel, so they intentionally stay dev-only rather than being promoted to "require".
    ->ignoreErrorsOnPackages(
        [
            'yiisoft/assets',
            'yiisoft/yii-view-renderer',
            'yiisoft/yii-http',
            'codeception/codeception',
            'phpunit/phpunit',
            'yiisoft/db',
        ],
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    )
    // Transitive dependency of yiisoft/yii-cycle, used only by the optional Cycle ORM schema provider.
    ->ignoreErrorsOnPackage('cycle/database', [ErrorType::SHADOW_DEPENDENCY])
    // Optional extensions, used only when available (guarded by function_exists() checks).
    ->ignoreErrorsOnExtensions(
        ['ext-posix', 'ext-zend-opcache'],
        [ErrorType::SHADOW_DEPENDENCY],
    );
