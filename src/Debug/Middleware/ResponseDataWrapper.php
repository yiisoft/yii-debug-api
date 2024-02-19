<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\Debug\Api\Debug\Exception\NotFoundException;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "DebugResponse", description: "Yii Debug Api response")]
#[OA\Schema(
    schema: "DebugSuccessResponse",
    allOf: [
        new OA\Schema(ref:"#/components/schemas/DebugResponse"),
        new OA\Schema(properties: [
            new OA\Property(property: "id",title: "ID",description: "ID", format: "string"),
            new OA\Property(property: "data",title: "Data",description: "Data", format: "object", nullable: true),
            new OA\Property(property: "error",title: "Error",description: "Error", format: "string", nullable: true, example: null),
            new OA\Property(property: "success",title: "Success",description: "Success", format: "boolean",example: true),
        ])
    ]
)]
#[OA\Schema(
    schema: "DebugNotFoundResponse",
    allOf: [
        new OA\Schema(ref:"#/components/schemas/DebugResponse"),
        new OA\Schema(properties: [
            new OA\Property(property: "id",title: "ID",description: "ID", format: "string"),
            new OA\Property(property: "data",title: "Data",description: "Data", format: "object", nullable: true, example: null),
            new OA\Property(property: "error",title: "Error",description: "Error", format: "string"),
            new OA\Property(property: "success",title: "Success",description: "Success", format: "boolean",example: false),
        ])
    ]
)]
final class ResponseDataWrapper implements MiddlewareInterface
{
    public function __construct(private DataResponseFactoryInterface $responseFactory, private CurrentRoute $currentRoute)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = [
            'id' => $this->currentRoute->getArgument('id'),
            'data' => null,
            'error' => null,
            'success' => true,
        ];
        try {
            $response = $handler->handle($request);
            if (!$response instanceof DataResponse) {
                return $response;
            }
            $data['data'] = $response->getData();
            $status = $response->getStatusCode();
            if ($status >= 400) {
                $data['success'] = false;
            }
        } catch (NotFoundException $exception) {
            $data['success'] = false;
            $data['error'] = $exception->getMessage();
            $status = Status::NOT_FOUND;
        }

        return $this->responseFactory->createResponse($data, $status);
    }
}
