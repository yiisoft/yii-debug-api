<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Command;

use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;

class PsalmCommand implements InspectorCommandInterface
{
    public const COMMAND_NAME = 'analyse/psalm';

    public function __construct(private Aliases $aliases)
    {
    }

    public static function getTitle(): string
    {
        return 'Psalm';
    }

    public static function getDescription(): string
    {
        return '';
    }

    public function run(): mixed
    {
        $projectDirectory = $this->aliases->get('@root');
        $debugDirectory = $this->aliases->get('@runtime/debug');

        $outputFilePath = $debugDirectory . DIRECTORY_SEPARATOR . 'psalm-report.json';

        $params = [
            'vendor/bin/psalm',
            '--report=' . $outputFilePath,
        ];

        $process = new Process($params);

        $process
            ->setWorkingDirectory($projectDirectory)
            ->setTimeout(null)
            ->run();

        return json_decode(
            file_get_contents($outputFilePath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
