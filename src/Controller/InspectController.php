<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Api\ApplicationState;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;

class InspectController
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

    public function index(): ResponseInterface
    {
        // TODO: how to get params for console or other param groups?
        $params = ApplicationState::$params;

        // TODO: also would be nice to inspect application config to access to di config at least

//        $config = ApplicationState::$config;
//        return $this->responseFactory->createResponse([$config->get('web'), $params]);

        return $this->responseFactory->createResponse($params);
    }

    public function classes(ContainerInterface $container): ResponseInterface
    {
        // TODO: how to get params for console or other param groups?
        $classes = [];

        $inspected = array_merge(get_declared_classes(), get_declared_interfaces());
        // TODO: think how to ignore heavy objects
        $patterns = [
            'ComposerAutoloaderInit',
            'Composer\\',
            'Yiisoft\\Yii\\Debug\\',
            'Yiisoft\\ErrorHandler\\ErrorHandler',
        ];
        foreach ($patterns as $pattern) {
            $inspected = array_filter($inspected, fn (string $class) => !str_starts_with($class, $pattern));
        }

        foreach ($inspected as $className) {
            $class = new ReflectionClass($className);

            if ($class->isInternal()) {
                continue;
            }

            $classes[] = $className;
        }
        sort($classes);

        return $this->responseFactory->createResponse($classes);
    }

    public function object(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->getQueryParams();
        $className = $request['classname'];

        $class = new ReflectionClass($className);

        if ($class->isInternal()) {
            throw new InvalidArgumentException('error');
        }

        $result = VarDumper::create($container->get($className))->asString();

        return $this->responseFactory->createResponse($result);
    }
}
