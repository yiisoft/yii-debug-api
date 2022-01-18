<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Middleware;

use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\Debug\Api\Exception\NotFoundException;

/**
 * @OA\Schema(schema="DebugResponse", description="Yii Debug Api response")
 * @OA\Schema(
 *     schema="DebugSuccessResponse",
 *     allOf={
 *          @OA\Schema(ref="#/components/schemas/DebugResponse"),
 *          @OA\Schema(
 *              @OA\Property(
 *                   description="ID",
 *                   title="ID",
 *                   property="id",
 *                   format="string"
 *              ),
 *              @OA\Property(
 *                   description="Data",
 *                   title="Data",
 *                   property="data",
 *                   type="object",
 *                   nullable=true
 *              ),
 *              @OA\Property(
 *                   description="Error",
 *                   title="Error",
 *                   property="error",
 *                   format="string",
 *                   nullable=true,
 *                   example=null
 *              ),
 *              @OA\Property(
 *                   description="Success",
 *                   title="Success",
 *                   property="success",
 *                   type="boolean",
 *                   example=true
 *              )
 *          )
 *     }
 * )
 * @OA\Schema(
 *     schema="DebugNotFoundResponse",
 *     allOf={
 *          @OA\Schema(ref="#/components/schemas/DebugResponse"),
 *          @OA\Schema(
 *              @OA\Property(
 *                   description="ID",
 *                   title="ID",
 *                   property="id",
 *                   format="string"
 *              ),
 *              @OA\Property(
 *                   description="Data",
 *                   title="Data",
 *                   property="data",
 *                   type="object",
 *                   nullable=true,
 *                   example=null
 *              ),
 *              @OA\Property(
 *                   description="Error",
 *                   title="Error",
 *                   property="error",
 *                   format="string",
 *              ),
 *              @OA\Property(
 *                   description="Success",
 *                   title="Success",
 *                   property="success",
 *                   type="boolean",
 *                   example=false
 *              )
 *          )
 *     }
 * )
 */
final class ResponseDataWrapper implements MiddlewareInterface
{
    public function __construct(private DataResponseFactoryInterface $responseFactory, private CurrentRoute $currentRoute)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $status = Status::OK;
        $data = [
            'id' => $this->currentRoute->getArgument('id'),
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
