<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Test;

use Codeception\Event\FailEvent;
use Codeception\Event\PrintResultEvent;
use Codeception\Event\TestEvent;
use Codeception\Events;
use Codeception\Extension;
use Codeception\Test\Descriptor;

final class CodeceptionJSONReporter extends Extension
{
    protected array $config = [
        'output-path' => __DIR__,
    ];
    private array $data = [];
    public const FILENAME = 'codeception-report.json';

    public function _initialize(): void
    {
        $this->_reconfigure(['settings' => ['silent' => true]]);
    }

    public static array $events = [
        Events::TEST_SUCCESS => 'success',
        Events::TEST_FAIL => 'fail',
        Events::TEST_ERROR => 'error',
        Events::RESULT_PRINT_AFTER => 'all',
    ];

    public function success(TestEvent $event): void
    {
        $this->data[] = [
            'file' => $this->getTestFilename($event),
            'test' => $this->getTestName($event),
            'status' => 'ok',
            'stacktrace' => [],
        ];
    }

    public function fail(FailEvent $event): void
    {
        $this->data[] = [
            'file' => $this->getTestFilename($event),
            'test' => $this->getTestName($event),
            'status' => 'fail',
            'stacktrace' => $event->getFail()->getTrace(),
        ];
    }

    public function error(FailEvent $event): void
    {
        $this->data[] = [
            'file' => $this->getTestFilename($event),
            'test' => $this->getTestName($event),
            'status' => 'error',
            'stacktrace' => $event->getFail()->getTrace(),
        ];
    }

    public function all(PrintResultEvent $event): void
    {
        file_put_contents($this->config['output-path'] . DIRECTORY_SEPARATOR . self::FILENAME, json_encode($this->data));
    }

    private function getTestName(TestEvent $event): string
    {
        return Descriptor::getTestFullName($event->getTest());
    }

    private function getTestFilename(TestEvent $event): string
    {
        return Descriptor::getTestFileName($event->getTest());
    }
}
