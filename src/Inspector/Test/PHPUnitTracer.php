<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Test;

use PHPUnit\Event\Event;
use PHPUnit\Event\Tracer\Tracer;

/**
 * @psalm-suppress InternalClass, InternalMethod
 */
class PHPUnitTracer implements Tracer
{
    public function __construct(private readonly PHPUnitJSONReporter $reporter)
    {
    }

    public function trace(Event $event): void
    {
        $this->reporter->log($event);
    }
}
