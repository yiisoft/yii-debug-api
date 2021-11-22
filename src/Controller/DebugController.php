<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRouteInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;

/**
 * Debug controller provides endpoints that expose information about requests processed that debugger collected.
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
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        return $this->responseFactory->createResponse($this->collectorRepository->getSummary());
    }

    /**
     * Summary about a processed request identified by ID specified.
     *
     * @param CurrentRouteInterface $currentRoute
     *
     * @return ResponseInterface
     */
    public function summary(CurrentRouteInterface $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getSummary($currentRoute->getArgument('id'));
        return $this->responseFactory->createResponse($data);
    }

    /**
     * Detail information about a processed request identified by ID.
     *
     * @param CurrentRouteInterface $currentRoute
     *
     * @return ResponseInterface response.
     */
    public function view(CurrentRouteInterface $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getDetail(
            $currentRoute->getArgument('id')
        );

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Dump information about a processed request identified by ID.
     *
     * @param CurrentRouteInterface $currentRoute
     *
     * @return ResponseInterface response.
     */
    public function dump(CurrentRouteInterface $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getDumpObject(
            $currentRoute->getArgument('id')
        );

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Object information about a processed request identified by ID.
     *
     * @param CurrentRouteInterface $currentRoute
     *
     * @return ResponseInterface response.
     */
    public function object(CurrentRouteInterface $currentRoute): ResponseInterface
    {
        $data = $this->collectorRepository->getObject(
            $currentRoute->getArgument('id'),
            $currentRoute->getArgument('objectId')
        );

        return $this->responseFactory->createResponse($data);
    }
}
