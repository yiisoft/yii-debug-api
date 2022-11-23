<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Command;

interface InspectorCommandInterface
{
    public static function getTitle(): string;

    public static function getDescription(): string;

    public function run(): mixed;
}
