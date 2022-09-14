<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\Debug\Api\Exception\NotFoundException;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;

/**
 * Debug controller provides endpoints that expose information about requests processed that debugger collected.
 *
 * @OA\Tag(
 *     name="yii-debug-api",
 *     description="Yii Debug API"
 * )
 */
final class DebugController
{
    private DataResponseFactoryInterface $responseFactory;
    private CollectorRepositoryInterface $collectorRepository;

    public function __construct(
        DataResponseFactoryInterface $responseFactory,
        CollectorRepositoryInterface $collectorRepository
    ) {
        $this->responseFactory = $responseFactory;
        $this->collectorRepository = $collectorRepository;
    }

    /**
     * List of requests processed.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api",
     *     description="List of requests processed",
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     )
     * )
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        return $this->responseFactory->createResponse($this->collectorRepository->getSummary());
    }

    /**
     * Summary about a processed request identified by ID specified.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api/summary/{id}",
     *     description="Summary about a processed request identified by ID specified",
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the summary"
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     */
    public function summary(CurrentRoute $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getSummary($currentRoute->getArgument('id'));
        return $this->responseFactory->createResponse($data);
    }

    /**
     * Detail information about a processed request identified by ID.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api/view/{id}/?collector={collector}",
     *     description="Detail information about a processed request identified by ID",
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the detail information"
     *     ),
     *     @OA\Parameter(
     *          name="collector",
     *          allowEmptyValue=true,
     *          @OA\Schema(type="string"),
     *          in="query",
     *          parameter="collector",
     *          description="Collector for getting the detail information"
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     */
    public function view(CurrentRoute $currentRoute, ServerRequestInterface $serverRequest): ResponseInterface
    {
        $data = $this->collectorRepository->getDetail(
            $currentRoute->getArgument('id')
        );

        $collectorClass = $serverRequest->getQueryParams()['collector'] ?? null;
        if ($collectorClass !== null) {
            $data = $data[$collectorClass] ?? throw new NotFoundException(
                sprintf("Requested collector doesn't exists: %s.", $collectorClass)
            );
        }

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Dump information about a processed request identified by ID.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api/dump/{id}/{collector}",
     *     description="Dump information about a processed request identified by ID",
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the dump information"
     *     ),
     *     @OA\Parameter(
     *          name="collector",
     *          allowEmptyValue=true,
     *          required=false,
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="collector",
     *          description="Collector for getting the dump information"
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     *
     * @param CurrentRoute $currentRoute
     *
     * @throws NotFoundException
     *
     * @return ResponseInterface response.
     */
    public function dump(CurrentRoute $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getDumpObject(
            $currentRoute->getArgument('id')
        );

        if ($currentRoute->getArgument('collector') !== null) {
            if (isset($data[$currentRoute->getArgument('collector')])) {
                $data = $data[$currentRoute->getArgument('collector')];
            } else {
                throw new NotFoundException('Requested collector doesn\'t exists.');
            }
        }

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Object information about a processed request identified by ID.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api/object/{id}/{objectId}",
     *     description="Object information about a processed request identified by ID",
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the object information"
     *     ),
     *     @OA\Parameter(
     *          name="objectId",
     *          required=true,
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="objectId",
     *          description="ID for getting the object information"
     *     ),
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     *
     * @param CurrentRoute $currentRoute
     *
     * @return ResponseInterface response.
     */
    public function object(CurrentRoute $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getObject(
            $currentRoute->getArgument('id'),
            $currentRoute->getArgument('objectId')
        );

        return $this->responseFactory->createResponse($data);
    }
}
