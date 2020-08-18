<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
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
        try {
            /** @var DataResponse $response */
            $response = $handler->handle($request);
            $data = $response->getData();
            return $response->withData(['id' => $request->getAttribute('id'), 'success' => true, 'data' => $data]);
        } catch (NotFoundException $exception) {
            $message = $exception->getMessage();
        }

        return $this->responseFactory->createResponse(
            ['id' => $request->getAttribute('id'), 'success' => false, 'error' => ['message' => $message]]
        );
    }
}
