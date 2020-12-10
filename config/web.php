<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepository;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

/**
 * @var $params array
 */

return [
    CollectorRepositoryInterface::class => static fn (StorageInterface $storage) => new CollectorRepository($storage)
];
