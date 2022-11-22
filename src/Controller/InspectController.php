<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use Throwable;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Api\Inspector\ApplicationState;
use Yiisoft\Yii\Debug\Api\Inspector\Command\CodeceptionCommand;
use Yiisoft\Yii\Debug\Api\Inspector\Command\PHPUnitCommand;
use Yiisoft\Yii\Debug\Api\Inspector\Command\PsalmCommand;

class InspectController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function config(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        $config = $container->get(ConfigInterface::class);

        $request = $request->getQueryParams();
        $group = $request['group'] ?? 'web';

        $data = $config->get($group);
        ksort($data);

        $response = VarDumper::create($data)->asJson(false, 255);
        return $this->responseFactory->createResponse(json_decode($response, null, 512, JSON_THROW_ON_ERROR));
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

        $inspected = [...get_declared_classes(), ...get_declared_interfaces()];
        // TODO: think how to ignore heavy objects
        $patterns = [
            fn (string $class) => !str_starts_with($class, 'ComposerAutoloaderInit'),
            fn (string $class) => !str_starts_with($class, 'Composer\\'),
            fn (string $class) => !str_starts_with($class, 'Yiisoft\\Yii\\Debug\\'),
            fn (string $class) => !str_starts_with($class, 'Yiisoft\\ErrorHandler\\ErrorHandler'),
            fn (string $class) => !str_contains($class, '@anonymous'),
            fn (string $class) => !is_subclass_of($class, Throwable::class),
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

        $reflection = new ReflectionClass($className);

        if ($reflection->isInternal()) {
            throw new InvalidArgumentException('Inspector cannot initialize internal classes.');
        }
        if ($reflection->implementsInterface(Throwable::class)) {
            throw new InvalidArgumentException('Inspector cannot initialize exceptions.');
        }

        $variable = $container->get($className);
        $result = VarDumper::create($variable)->asJson(false, 3);

        return $this->responseFactory->createResponse([
            'object' => json_decode($result, null, 512, JSON_THROW_ON_ERROR),
            'path' => $reflection->getFileName(),
        ]);
    }

    public function command(ServerRequestInterface $request, ContainerInterface $container): ResponseInterface
    {
        // TODO: would be great to recognise test engine automatically
        $map = [
            'test/phpunit' => PHPUnitCommand::class,
            'test/codeception' => CodeceptionCommand::class,
            'analyse/psalm' => PsalmCommand::class,
        ];

        $request = $request->getQueryParams();
        $commandName = $request['command'] ?? 'test/codeception';

        if (!array_key_exists($commandName, $map)) {
            throw new InvalidArgumentException('Unknown command');
        }

        $result = $container->get($map[$commandName])->run();

        return $this->responseFactory->createResponse($result);
    }
}
