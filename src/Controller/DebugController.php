<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Collector\WebAppInfoCollector;
use Yiisoft\Yii\Debug\Debugger;

/**
 * Debug controller provides browsing over available debug logs.
 */
class DebugController
{
    private DataResponseFactoryInterface $responseFactory;
    private CollectorRepositoryInterface $collectorRepository;
    private Debugger $debugger;

    public function __construct(
        DataResponseFactoryInterface $responseFactory,
        CollectorRepositoryInterface $collectorRepository,
        Debugger $debugger
    ) {
        $this->responseFactory = $responseFactory;
        $this->collectorRepository = $collectorRepository;
        $this->debugger = $debugger;
    }

    /**
     * Index action
     *
     * @return ResponseInterface
     */
    public function index(): ResponseInterface
    {
        return $this->responseFactory->createResponse($this->collectorRepository->getSummary());
    }

    /**
     * Summary action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function summary(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->collectorRepository->getSummary($request->getAttribute('id'));
        return $this->responseFactory->createResponse($data);
    }

    /**
     * Detail action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface response.
     */
    public function view(ServerRequestInterface $request): ResponseInterface
    {
        $data = $this->collectorRepository->getDetail(
            $request->getAttribute('id') ?? $this->debugger->getId(),
            $request->getAttribute('collector') ?? WebAppInfoCollector::class
        );

        return $this->responseFactory->createResponse($data);
    }
}
