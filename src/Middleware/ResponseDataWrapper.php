<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Yii\Debug\Api\Exception\NotFoundException;

/**
 * @internal
 */
final class ResponseDataWrapper implements MiddlewareInterface
{
    private DataResponseFactoryInterface $responseFactory;

    public function __construct(DataResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $status = Status::OK;
        $data = [
            'id' => $request->getAttribute('id'),
            'data' => null,
            'error' => null,
            'success' => true,
        ];
        try {
            /** @var DataResponse $response */
            $response = $handler->handle($request);
            $data['data'] = $response->getData();
        } catch (NotFoundException $exception) {
            $data['success'] = false;
            $data['error'] = $exception->getMessage();
            $status = Status::NOT_FOUND;
        }

        return $this->responseFactory->createResponse($data, $status);
    }
}
