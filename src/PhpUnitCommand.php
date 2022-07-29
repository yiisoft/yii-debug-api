<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api;

use Symfony\Component\Process\Process;
use Yiisoft\Aliases\Aliases;

class PhpUnitCommand
{
    public function __construct(private Aliases $aliases)
    {
    }

    public function run()
    {
        $params = [
            'vendor/bin/codecept',
            'run',
            '--silent',
        ];

        $process = new Process($params);

        $projectDirectory = $this->aliases->get('@root');
        $process
            ->setWorkingDirectory($projectDirectory)
            ->setTimeout(null)
            ->run();

        return json_decode(file_get_contents(
            // TODO: use relative path
            $projectDirectory . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'output.json'
        ), true);
    }
}
