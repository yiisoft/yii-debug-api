<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\Inspector\Command\BashCommand;

final class ConfigController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function read(Aliases $aliases): ResponseInterface
    {
        $command = new BashCommand($aliases, [
            'composer',
            'yii-config-merge-plan',
        ]);
        $output = $command->run()->getResult();
        $mergePlanPath = substr($output, 0, strpos($output, "Xdebug: [Step Debug]") ?: -1);

        if (!file_exists($mergePlanPath)) {
            throw new Exception(
                sprintf(
                    'Could not find composer.json by the path "%s".',
                    $mergePlanPath,
                )
            );
        }

        $content = require $mergePlanPath;

        $result = [
            'path' => $mergePlanPath,
            'data' => $content,
        ];

        return $this->responseFactory->createResponse($result);
    }
}
