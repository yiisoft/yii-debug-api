<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;
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
     * @param string|null $id debug data tag.
     * @param string|null $collector debug collector ID.
     * @return array response.
     */
    public function view(?string $id = null, ?string $collector = null): array
    {
        // TODO: implement
        return [];
    }

    /**
     * Toolbar action
     *
     * @param string $id
     * @return array
     */
    public function summary(?string $id = null): object
    {
        // TODO: implement
        return $this->responseFactory->createResponse(['test' => $this->debugger->getId()]);
    }
}
