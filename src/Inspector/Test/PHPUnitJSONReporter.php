<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Test;

use PHPUnit\Event\Event;
use PHPUnit\Event\Test\ConsideredRisky;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErrorTriggered;
use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\NoticeTriggered;
use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PhpNoticeTriggered;
use PHPUnit\Event\Test\PhpunitErrorTriggered;
use PHPUnit\Event\Test\PhpunitNoticeTriggered;
use PHPUnit\Event\Test\PhpunitWarningTriggered;
use PHPUnit\Event\Test\PhpWarningTriggered;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\WarningTriggered;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class PHPUnitJSONReporter implements Extension
{
    public const FILENAME = 'phpunit-report.json';
    public const ENVIRONMENT_VARIABLE_DIRECTORY_NAME = 'REPORTER_OUTPUT_PATH';

    private array $data = [];

    public function __construct()
    {
    }

    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters
    ): void
    {
        $facade->registerTracer(new PHPUnitTracer($this));
    }

    public function __destruct()
    {
        $this->saveResult();
    }

    public function saveResult(): void
    {
        $path = getenv(self::ENVIRONMENT_VARIABLE_DIRECTORY_NAME) ?: getcwd();
        ksort($this->data);

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . self::FILENAME,
            json_encode(array_values($this->data), JSON_THROW_ON_ERROR)
        );
    }

    public function logPassed(Passed|Finished $event): void
    {
        $parsedName = $event->test()->id();
        if (array_key_exists($parsedName, $this->data)) {
            return;
        }

        $this->data[$parsedName] = [
            'file' => $event->test()->file(),
            'test' => $parsedName,
            'time' => $event->telemetryInfo()->durationSincePrevious()->asFloat(),
            'status' => 'ok',
            'stacktrace' => [],
        ];
    }

    public function logErrored(Failed|MarkedIncomplete|Errored $event): void
    {
        $parsedName = $event->test()->id();
        if (array_key_exists($parsedName, $this->data)) {
            return;
        }

        $this->data[$parsedName] = [
            'file' => $event->test()->file(),
            'test' => $parsedName,
            'time' => $event->telemetryInfo()->durationSincePrevious()->asFloat(),
            'status' => $event->throwable()->message(),
            'stacktrace' => $event->throwable()->stackTrace(),
        ];
    }

    public function logMessage(WarningTriggered|PhpWarningTriggered|PhpunitWarningTriggered|NoticeTriggered|PhpNoticeTriggered|PhpunitNoticeTriggered|ErrorTriggered|PhpunitErrorTriggered|Skipped|ConsideredRisky $event): void
    {
        $parsedName = $event->test()->id();
        if (array_key_exists($parsedName, $this->data)) {
            return;
        }

        $this->data[$parsedName] = [
            'file' => $event->test()->file(),
            'test' => $parsedName,
            'time' => $event->telemetryInfo()->durationSincePrevious()->asFloat(),
            'status' => $event->message(),
            'stacktrace' => [],
        ];
    }

    public function log(Event $event)
    {
        if ($event instanceof Passed
            || $event instanceof Finished
        ) {
            $this->logPassed($event);
        } elseif ($event instanceof Failed
            || $event instanceof Errored
            || $event instanceof MarkedIncomplete
        ) {
            $this->logErrored($event);
        } elseif (
            $event instanceof WarningTriggered
            || $event instanceof PhpWarningTriggered
            || $event instanceof PhpunitWarningTriggered
            || $event instanceof NoticeTriggered
            || $event instanceof PhpNoticeTriggered
            || $event instanceof PhpunitNoticeTriggered
            || $event instanceof ErrorTriggered
            || $event instanceof PhpunitErrorTriggered
            || $event instanceof Skipped
            || $event instanceof ConsideredRisky
        ) {
            $this->logMessage($event);
        }
    }
}
