<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepository;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;

/**
 * @var $params array
 */

return [
    CollectorRepositoryInterface::class => static function (ContainerInterface $container) use ($params) {
        $aliases = $container->get(Aliases::class);
        return new CollectorRepository($aliases->get($params['yiisoft/yii-debug']['path']));
    },
];
