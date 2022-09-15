<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Command;

use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Inspector\Test\PHPUnitJSONReporter;

class PHPUnitCommand
{
    public function __construct(private Aliases $aliases)
    {
    }

    public function run(): mixed
    {
        $projectDirectory = $this->aliases->get('@root');
        $debugDirectory = $this->aliases->get('@runtime/debug');

        $extension = PHPUnitJSONReporter::class;
        $params = [
            'vendor/bin/phpunit',
            '--printer',
            $extension,
            '-vvv',
        ];

        $process = new Process($params);

        $process
            ->setEnv([PHPUnitJSONReporter::ENVIRONMENT_VARIABLE_DIRECTORY_NAME => $debugDirectory])
            ->setWorkingDirectory($projectDirectory)
            ->setTimeout(null)
            ->run();

        return json_decode(
            file_get_contents($debugDirectory . DIRECTORY_SEPARATOR . PHPUnitJSONReporter::FILENAME),
            true
        );
    }
}
