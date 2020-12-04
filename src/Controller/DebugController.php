<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;

/**
 * Debug controller provides endpoints that expose information about requests processed that debugger collected.
 */
class DebugController
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
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function summary(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->collectorRepository->getSummary($request->getAttribute('id'));
        return $this->responseFactory->createResponse($data);
    }

    /**
     * Detail information about a processed request identified by ID.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface response.
     */
    public function view(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->collectorRepository->getDetail(
            $request->getAttribute('id')
        );

        return $this->responseFactory->createResponse($data);
    }

    /**
     * Dump information about a processed request identified by ID.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface response.
     */
    public function object(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->collectorRepository->getDumpObject(
            $request->getAttribute('id')
        );

        return $this->responseFactory->createResponse($data);
    }
}
