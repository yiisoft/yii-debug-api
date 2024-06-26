<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

use function Safe\opcache_get_status;

final class OpcacheController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(): ResponseInterface
    {
        return $this->responseFactory->createResponse([
            'status' => opcache_get_status(true),
            'configuration' => opcache_get_configuration(),
        ]);
    }
}
