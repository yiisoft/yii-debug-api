<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use InvalidArgumentException;
use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Api\ApplicationState;
use Yiisoft\Yii\Debug\Api\PhpUnitCommand;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;

class InspectController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function config(ContainerInterface $container): ResponseInterface
    {
        $config = $container->get(ConfigInterface::class);

        // TODO: pass different envs
        $data = $config->get('web');
        ksort($data);
        foreach ($data as &$value) {
//            $value = get_debug_type($value);
            if ($value instanceof Closure) {
                $value = VarDumper::create($value)->asString();
            }
        }

        return $this->responseFactory->createResponse($data);
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
        // TODO: pass different commands
//        $request = $request->getQueryParams();
//        $className = $request['command'];

        $result = $command->run();

        return $this->responseFactory->createResponse($result);
    }
}
