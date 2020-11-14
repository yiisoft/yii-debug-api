<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Repository;

interface CollectorRepositoryInterface
{
    public function getSummary(?string $id = null): array;

    public function getDetail(string $id, string $collector): array;

    public function getDumpObject(string $id, string $collector): array;
}
