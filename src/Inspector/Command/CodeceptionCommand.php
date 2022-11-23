<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Command;

use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Inspector\Test\CodeceptionJSONReporter;

class CodeceptionCommand implements InspectorCommandInterface
{
    public const COMMAND_NAME = 'test/codeception';

    public function __construct(private Aliases $aliases)
    {
    }

    public static function getTitle(): string
    {
        return 'Codeception';
    }

    public static function getDescription(): string
    {
        return '';
    }

    public function run(): mixed
    {
        $projectDirectory = $this->aliases->get('@root');
        $debugDirectory = $this->aliases->get('@runtime/debug');

        $extension = CodeceptionJSONReporter::class;
        $params = [
            'vendor/bin/codecept',
            'run',
            '--silent',
            '-e',
            $extension,
            '--override',
            "extensions: config: {$extension}: output-path: {$debugDirectory}",
            '-vvv',
        ];

        $process = new Process($params);

        $process
            ->setWorkingDirectory($projectDirectory)
            ->setTimeout(null)
            ->run();

        return json_decode(
            file_get_contents($debugDirectory . DIRECTORY_SEPARATOR . CodeceptionJSONReporter::FILENAME),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
