<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Repository;

interface CollectorRepositoryInterface
{
    public function getSummary(?string $id = null): array;

    public function getDetail(string $id): array;
}
