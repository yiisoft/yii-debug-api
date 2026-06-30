<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Test;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Util\TestDox\NamePrettifier;
use ReflectionClass;
use Throwable;

/**
 * @psalm-suppress InternalClass, InternalMethod, UndefinedClass
 */
class PHPUnitJSONReporter
{
    public const FILENAME = 'phpunit-report.json';
    public const ENVIRONMENT_VARIABLE_DIRECTORY_NAME = 'REPORTER_OUTPUT_PATH';

    private array $data = [];
    private NamePrettifier $prettifier;

    public function __construct()
    {
        $this->prettifier = new NamePrettifier();
    }

    public function printResult(mixed $result): void
    {
        $path = getenv(self::ENVIRONMENT_VARIABLE_DIRECTORY_NAME) ?: getcwd();
        ksort($this->data);

        file_put_contents(
            $path . DIRECTORY_SEPARATOR . self::FILENAME,
            json_encode(array_values($this->data), JSON_THROW_ON_ERROR)
        );
    }

    public function write(string $buffer): void
    {
        $this->data = [];
    }

    public function addError(Test $test, Throwable $t, float $time): void
    {
        $this->logErroredTest($test, $t);
    }

    public function addWarning(Test $test, Throwable $e, float $time): void
    {
        $this->logErroredTest($test, $e);
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        $this->logErroredTest($test, $e);
    }

    public function addIncompleteTest(Test $test, Throwable $t, float $time): void
    {
        $this->logErroredTest($test, $t);
    }

    public function addRiskyTest(Test $test, Throwable $t, float $time): void
    {
        $this->logErroredTest($test, $t);
    }

    public function addSkippedTest(Test $test, Throwable $t, float $time): void
    {
        $this->logErroredTest($test, $t);
    }

    public function startTestSuite(TestSuite $suite): void
    {
    }

    public function endTestSuite(TestSuite $suite): void
    {
    }

    public function startTest(Test $test): void
    {
    }

    public function endTest(Test $test, float $time): void
    {
        if (!$test instanceof TestCase) {
            return;
        }
        if (!$test->status()->isSuccess()) {
            return;
        }

        $parsedName = $this->parseName($test);

        $this->data[$parsedName] = [
            'file' => $this->parseFilename($test),
            'test' => $parsedName,
            'status' => 'ok',
            'stacktrace' => [],
        ];
    }

    private function parseName(Test $test): string
    {
        if ($test instanceof TestCase) {
            return $test::class . '::' . $test->name();
        }
        return $this->prettifier->prettifyTestClass($test::class);
    }

    private function parseFilename(Test $test): string
    {
        $reflection = new ReflectionClass($test);

        return $reflection->getFileName();
    }

    private function logErroredTest(Test $test, Throwable $t): void
    {
        $parsedName = $this->parseName($test);

        $this->data[$parsedName] = [
            'file' => $this->parseFilename($test),
            'test' => $parsedName,
            'status' => $t->getMessage(),
            'stacktrace' => $t->getTrace(),
        ];
    }
}
