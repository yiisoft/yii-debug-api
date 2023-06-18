<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Assets\AssetPublisherInterface;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Mutex\Synchronizer;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\Debug\Api\Exception\NotFoundException;
use Yiisoft\Yii\Debug\Api\Exception\PackageNotInstalledException;
use Yiisoft\Yii\Debug\Api\HtmlViewProviderInterface;
use Yiisoft\Yii\Debug\Api\ModuleFederationProviderInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Debugger;
use Yiisoft\Yii\View\ViewRenderer;

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
    public function __construct(private DataResponseFactoryInterface $responseFactory, private CollectorRepositoryInterface $collectorRepository)
    {
    }

    /**
     * List of requests processed.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api",
     *     description="List of requests processed",
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     )
     * )
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
     *
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the summary"
     *     ),
     *
     *     @OA\Parameter(
     *          name="timeout",
     *          required=false,
     *
     *          @OA\Schema(type="integer"),
     *          in="query",
     *          parameter="timeout",
     *          description="Timeout to wait for the debug entry saving. 0 means do not wait."
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     */
    public function summary(
        CurrentRoute $currentRoute,
        ServerRequestInterface $serverRequest,
        Synchronizer $synchronizer,
    ): ResponseInterface {
        $id = $currentRoute->getArgument('id');
        $timeout = max(0, (int)($serverRequest->getQueryParams()['timeout'] ?? 0));

        $data = $synchronizer->execute(Debugger::SAVING_MUTEX_NAME . $id, fn () => $this->collectorRepository->getSummary($id), $timeout);
        return $this->responseFactory->createResponse($data);
    }

    /**
     * Detail information about a processed request identified by ID.
     *
     * @OA\Get(
     *     tags={"yii-debug-api"},
     *     path="/debug/api/view/{id}/?collector={collector}",
     *     description="Detail information about a processed request identified by ID",
     *
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the detail information"
     *     ),
     *
     *     @OA\Parameter(
     *          name="collector",
     *          allowEmptyValue=true,
     *
     *          @OA\Schema(type="string"),
     *          in="query",
     *          parameter="collector",
     *          description="Collector for getting the detail information"
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     */
    public function view(
        CurrentRoute $currentRoute,
        ServerRequestInterface $serverRequest,
        ContainerInterface $container,
    ): ResponseInterface {
        $id = $currentRoute->getArgument('id');
        $data = $this->collectorRepository->getDetail($id);

        $collectorClass = $serverRequest->getQueryParams()['collector'] ?? null;
        if ($collectorClass !== null) {
            $data = $data[$collectorClass] ?? throw new NotFoundException(
                sprintf("Requested collector doesn't exist: %s.", $collectorClass)
            );
        }
        if (is_subclass_of($collectorClass, HtmlViewProviderInterface::class)) {
            return $this->createHtmlPanelResponse($container, $collectorClass, $data);
        }
        if (is_subclass_of($collectorClass, ModuleFederationProviderInterface::class)) {
            return $this->createJsPanelResponse($container, $collectorClass, $data);
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
     *
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the dump information"
     *     ),
     *
     *     @OA\Parameter(
     *          name="collector",
     *          allowEmptyValue=true,
     *          required=false,
     *
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="collector",
     *          description="Collector for getting the dump information"
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     *
     * @throws NotFoundException
     *
     * @return ResponseInterface response.
     */
    public function dump(CurrentRoute $currentRoute): ResponseInterface
    {
        $id = $currentRoute->getArgument('id');
        $data = $this->collectorRepository->getDumpObject($id);

        $collector = $currentRoute->getArgument('collector');
        if ($collector !== null) {
            $data = $data[$collector] ?? throw new NotFoundException('Requested collector does not exist.');
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
     *
     *     @OA\Parameter(
     *          name="id",
     *          required=true,
     *
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="id",
     *          description="Request ID for getting the object information"
     *     ),
     *
     *     @OA\Parameter(
     *          name="objectId",
     *          required=true,
     *
     *          @OA\Schema(type="string"),
     *          in="path",
     *          parameter="objectId",
     *          description="ID for getting the object information"
     *     ),
     *
     *     @OA\Response(
     *          response="200",
     *          description="Success",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugSuccessResponse")
     *              }
     *          )
     *     ),
     *
     *     @OA\Response(
     *          response="404",
     *          description="Not found",
     *
     *          @OA\JsonContent(
     *              allOf={
     *                  @OA\Schema(ref="#/components/schemas/DebugNotFoundResponse")
     *              }
     *          )
     *     )
     * )
     *
     * @return ResponseInterface response.
     */
    public function object(CurrentRoute $currentRoute): ResponseInterface
    {
        $id = $currentRoute->getArgument('id');
        $objectId = $currentRoute->getArgument('objectId');

        $data = $this->collectorRepository->getObject($id, $objectId);

        return $this->responseFactory->createResponse($data);
    }

    private function createJsPanelResponse(
        ContainerInterface $container,
        string $collectorClass,
        mixed $data
    ): DataResponse {
        $asset = $collectorClass::getAsset();
        $module = $asset->getModule();
        $scope = $asset->getScope();
        /**
         * @psalm-suppress UndefinedClass
         */
        if (
            !class_exists(AssetManager::class)
            || !class_exists(AssetPublisherInterface::class)
            || !$container->has(AssetManager::class)
            || !$container->has(AssetPublisherInterface::class)
        ) {
            throw new PackageNotInstalledException(
                'yiisoft/assets',
                sprintf(
                    '"%s" or "%s" is not defined in the dependency container.',
                    AssetManager::class,
                    AssetPublisherInterface::class,
                ),
            );
        }
        /**
         * @psalm-suppress UndefinedClass
         */
        $assetManager = $container->get(AssetManager::class);
        $assetManager->register($asset::class);
        /**
         * @psalm-suppress UndefinedClass
         */
        $assetPublisher = $container->get(AssetPublisherInterface::class);
        $assetPublisher->publish($asset);

        $js = $assetManager->getJsFiles();

        $urls = end($js);

        return $this->responseFactory->createResponse([
            '__isPanelRemote__' => true,
            'url' => $urls[0],
            'module' => $module,
            'scope' => $scope,
            'data' => $data,
        ]);
    }

    private function createHtmlPanelResponse(
        ContainerInterface $container,
        string $collectorClass,
        mixed $data
    ): DataResponse {
        if (!class_exists(ViewRenderer::class) || !$container->has(ViewRenderer::class)) {
            /**
             * @psalm-suppress UndefinedClass
             */
            throw new PackageNotInstalledException(
                'yiisoft/yii-view',
                sprintf(
                    '"%s" is not defined in the dependency container.',
                    ViewRenderer::class,
                )
            );
        }
        $viewRenderer = $container->get(ViewRenderer::class);
        $viewDirectory = dirname($collectorClass::getView());
        $viewPath = basename($collectorClass::getView());

        return $viewRenderer
            ->withViewPath($viewDirectory)
            ->renderPartial($viewPath, ['data' => $data, 'collectorClass' => $collectorClass]);
    }
}
