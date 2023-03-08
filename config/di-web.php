<?php

declare(strict_types=1);

use Cycle\Database\DatabaseProviderInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Yii\Debug\Api\Inspector\ActiveRecord\ActiveRecordSchemaProvider;
use Yiisoft\Yii\Debug\Api\Inspector\Database\Cycle\CycleSchemaProvider;
use Yiisoft\Yii\Debug\Api\Inspector\Database\SchemaProviderInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepository;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

/**
 * @var $params array
 */

return [
    CollectorRepositoryInterface::class => static fn (StorageInterface $storage) => new CollectorRepository($storage),
    SchemaProviderInterface::class => function (ContainerInterface $container) {
        if ($container->has(DatabaseProviderInterface::class)) {
            return $container->get(CycleSchemaProvider::class);
        }

        if ($container->has(ConnectionInterface::class)) {
            return $container->get(ActiveRecordSchemaProvider::class);
        }

        throw new LogicException(
            sprintf(
                'Inspecting database is not available. Configure "%s" service to be able to inspect database.',
                ConnectionInterface::class,
            )
        );
    },
];
