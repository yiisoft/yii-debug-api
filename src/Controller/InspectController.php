<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use FilesystemIterator;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;
use Yiisoft\Aliases\Aliases;
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

    public function files(Aliases $aliases, ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->getQueryParams();
        $path = $request['path'] ?? '';

        $rootPath = $aliases->get('@root');

        $destination = realpath($rootPath . $path);

        if (!file_exists($destination)) {
            throw new InvalidArgumentException(sprintf('Destination "%s" does not exist', $destination));
        }


        if (!is_dir($destination)) {
            $file = new SplFileInfo($destination);
            return $this->responseFactory->createResponse(
                array_merge([
                    'directory' => $this->removeBasePath($rootPath, dirname($destination)),
                    'content' => file_get_contents($destination),
                    'path' => $this->removeBasePath($rootPath, $destination),
                    'absolutePath' => $destination,
                ],
                    $this->serializeFileInfo($file)
                )
            );
        }

        /**
         * @var $directoryIterator SplFileInfo[]
         */
        $directoryIterator = new RecursiveDirectoryIterator(
            $destination,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO
        );

        $files = [];
        foreach ($directoryIterator as $file) {
            if ($file->getBasename() === '.') {
                continue;
            }

            $path = $file->getPathName();
            if ($file->isDir()) {
                if ($file->getBasename() === '..') {
                    $path = realpath($path);
                }
                $path .= '/';
            }
            /**
             * Check if path is inside the application directory
             */
            if (!str_starts_with($path, $rootPath)) {
                continue;
            }
            $path = $this->removeBasePath($rootPath, $path);
            $files[] = array_merge([
                'path' => $path,
            ],
                $this->serializeFileInfo($file)
            );
        }

        return $this->responseFactory->createResponse($files);
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
        $queryParams = $request->getQueryParams();
        $className = $queryParams['classname'];

        $class = new ReflectionClass($className);

        if ($class->isInternal()) {
            throw new InvalidArgumentException('error');
        }

        $variable = $container->get($className);
        $result = VarDumper::create($variable)->asJson();

        return $this->responseFactory->createResponse(json_decode($result, null, 512, JSON_THROW_ON_ERROR));
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

    private function removeBasePath(string $rootPath, string $path): string|array|null
    {
        return preg_replace(
            '/^' . preg_quote($rootPath, '/') . '/',
            '',
            $path,
            1
        );
    }

    private function getUserOwner(int $uid): array
    {
        if ($uid === 0 || !function_exists('posix_getpwuid') || false === ($info = posix_getpwuid($uid))) {
            return [
                'id' => $uid,
            ];
        }
        return [
            'uid' => $info['uid'],
            'gid' => $info['gid'],
            'name' => $info['name'],
        ];
    }

    private function getGroupOwner(int $gid): array
    {
        if ($gid === 0 || !function_exists('posix_getgrgid') || false === ($info = posix_getgrgid($gid))) {
            return [
                'id' => $gid,
            ];
        }
        return [
            'gid' => $info['gid'],
            'name' => $info['name'],
        ];
    }

    private function serializeFileInfo(SplFileInfo $file): array
    {
        return [
            'baseName' => $file->getBasename(),
            'extension' => $file->getExtension(),
            'user' => $this->getUserOwner((int) $file->getOwner()),
            'group' => $this->getGroupOwner((int) $file->getGroup()),
            'size' => $file->getSize(),
            'type' => $file->getType(),
            'permissions' => substr(sprintf('%o', $file->getPerms()), -4),
        ];
    }
}
