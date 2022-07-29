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
use Yiisoft\Yii\Debug\Api\PhpUnitCommand;
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

    public function config(): ResponseInterface
    {
        // TODO: how to get params for console or other param groups?
        $params = ApplicationState::$params;

        // TODO: also would be nice to inspect application config to access to di config at least

//        $config = ApplicationState::$config;
//        return $this->responseFactory->createResponse([$config->get('web'), $params]);

        return $this->responseFactory->createResponse($params);
    }

    public function params(): ResponseInterface
    {
        $params = ApplicationState::$params;
        ksort($params);

        return $this->responseFactory->createResponse($params);
    }

    public function classes(ContainerInterface $container): ResponseInterface
    {
        // TODO: how to get params for console or other param groups?
        $classes = [];

        $inspected = array_merge(get_declared_classes(), get_declared_interfaces());
        // TODO: think how to ignore heavy objects
        $patterns = [
            fn (string $class) => !str_starts_with($class, 'ComposerAutoloaderInit'),
            fn (string $class) => !str_starts_with($class, 'Composer\\'),
            fn (string $class) => !str_starts_with($class, 'Yiisoft\\Yii\\Debug\\'),
            fn (string $class) => !str_starts_with($class, 'Yiisoft\\ErrorHandler\\ErrorHandler'),
            fn (string $class) => !str_contains($class, '@anonymous'),
        ];
        foreach ($patterns as $patternFunction) {
            $inspected = array_filter($inspected, $patternFunction);
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

    public function command(ContainerInterface $container, PhpUnitCommand $command): ResponseInterface
    {
//        $request = $request->getQueryParams();
//        $className = $request['command'];

        $result = $command->run();

        return $this->responseFactory->createResponse($result);
    }
}
