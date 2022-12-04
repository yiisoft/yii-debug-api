<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Cycle\Database\ColumnInterface;
use Cycle\Database\DatabaseProviderInterface;
use FilesystemIterator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Message;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RecursiveDirectoryIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Throwable;
use Yiisoft\ActiveRecord\ActiveRecordFactory;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Config\ConfigInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;
use Yiisoft\Db\Schema\TableSchemaInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Debug\Api\Inspector\ActiveRecord\Common;
use Yiisoft\Yii\Debug\Api\Inspector\ApplicationState;
use Yiisoft\Yii\Debug\Api\Inspector\CommandInterface;
use Yiisoft\Yii\Debug\Api\Repository\CollectorRepositoryInterface;
use Yiisoft\Yii\Debug\Collector\RequestCollector;

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

    public function getTranslations(ContainerInterface $container): ResponseInterface
    {
        /**
         * @var $categorySources CategorySource[]
         */
        $categorySources = $container->get('tag@translation.categorySource');

        $params = ApplicationState::$params;

        $locales = array_keys($params['locale']['locales']);
        if ($locales === []) {
            throw new RuntimeException(
                'Unable to determine list of available locales. ' .
                'Make sure that "$params[\'locale\'][\'locales\']" contains all available locales.'
            );
        }
        $messages = [];
        foreach ($categorySources as $categorySource) {
            $messages[$categorySource->getName()] = [];

            try {
                foreach ($locales as $locale) {
                    $messages[$categorySource->getName()][$locale] = $categorySource->getMessages($locale);
                }
            } catch (Throwable) {
            }
        }

        $response = VarDumper::create($messages)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
    }

    public function putTranslation(ContainerInterface $container, ServerRequestInterface $request): ResponseInterface
    {
        /**
         * @var $categorySources CategorySource[]
         */
        $categorySources = $container->get('tag@translation.categorySource');

        $body = $request->getParsedBody();
        $categoryName = $body['category'] ?? '';
        $locale = $body['locale'] ?? '';
        $translationId = $body['translation'] ?? '';
        $newMessage = $body['message'] ?? '';

        $categorySource = null;
        foreach ($categorySources as $possibleCategorySource) {
            if ($possibleCategorySource->getName() === $categoryName) {
                $categorySource = $possibleCategorySource;
            }
        }
        if ($categorySource === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid category name "%s". Only the following categories are available: "%s"',
                    $categoryName,
                    implode(
                        '", "',
                        array_map(fn (CategorySource $categorySource) => $categorySource->getName(), $categorySources)
                    )
                )
            );
        }
        $messages = $categorySource->getMessages($locale);
        $messages = array_replace_recursive($messages, [
            $translationId => [
                'message' => $newMessage,
            ],
        ]);
        $categorySource->write($locale, $messages);

        $result = [$locale => $messages];
        $response = VarDumper::create($result)->asPrimitives(255);
        return $this->responseFactory->createResponse($response);
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

        $destination = $this->removeBasePath($rootPath, $path);

        if (!str_starts_with('/', $destination)) {
            $destination = '/' . $destination;
        }

        $destination = realpath($rootPath . $destination);

        if (!file_exists($destination)) {
            throw new InvalidArgumentException(sprintf('Destination "%s" does not exist', $destination));
        }

        if (!is_dir($destination)) {
            $file = new SplFileInfo($destination);
            return $this->responseFactory->createResponse(
                array_merge(
                    [
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
            $files[] = array_merge(
                [
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

    public function getCommands(ConfigInterface $config): ResponseInterface
    {
        $params = $config->get('params');
        $commandMap = $params['yiisoft/yii-debug-api']['inspector']['commandMap'] ?? [];

        $result = [];
        foreach ($commandMap as $groupName => $commands) {
            foreach ($commands as $name => $command) {
                if (!is_subclass_of($command, CommandInterface::class)) {
                    continue;
                }
                $result[] = [
                    'name' => $name,
                    'title' => $command::getTitle(),
                    'group' => $groupName,
                    'description' => $command::getDescription(),
                ];
            }
        }

        return $this->responseFactory->createResponse($result);
    }

    public function routes(RouteCollectionInterface $routeCollection): ResponseInterface
    {
        $routes = [];
        foreach ($routeCollection->getRoutes() as $route) {
            $data = $route->__debugInfo();
            $routes[] = [
                'name' => $data['name'],
                'hosts' => $data['hosts'],
                'pattern' => $data['pattern'],
                'methods' => $data['methods'],
                'defaults' => $data['defaults'],
                'override' => $data['override'],
                'middlewares' => $data['middlewareDefinitions'],
            ];
        }
        $response = VarDumper::create($routes)->asJson(false, 5);
        return $this->responseFactory->createResponse(json_decode($response, null, 512, JSON_THROW_ON_ERROR));
    }

    public function runCommand(
        ServerRequestInterface $request,
        ContainerInterface $container,
        ConfigInterface $config
    ): ResponseInterface {
        $params = $config->get('params');
        $commandMap = $params['yiisoft/yii-debug-api']['inspector']['commandMap'] ?? [];

        /**
         * @var array<string, class-string<CommandInterface>> $commandList
         */
        $commandList = [];
        foreach ($commandMap as $commands) {
            foreach ($commands as $name => $command) {
                if (!is_subclass_of($command, CommandInterface::class)) {
                    continue;
                }
                $commandList[$name] = $command;
            }
        }

        $request = $request->getQueryParams();
        $commandName = $request['command'] ?? null;

        if ($commandName === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Command must not be null. Available commands: "%s".',
                    implode('", "', $commandList)
                )
            );
        }

        if (!array_key_exists($commandName, $commandList)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown command "%s". Available commands: "%s".',
                    $commandName,
                    implode('", "', $commandList)
                )
            );
        }

        $commandClass = $commandList[$commandName];
        /**
         * @var $command CommandInterface
         */
        $command = $container->get($commandClass);

        $result = $command->run();

        return $this->responseFactory->createResponse([
            'status' => $result->getStatus(),
            'result' => $result->getResult(),
            'error' => $result->getErrors(),
        ]);
    }

    public function getTables(
        ContainerInterface $container,
        ActiveRecordFactory $arFactory,
    ): ResponseInterface {
        if ($container->has(DatabaseProviderInterface::class)) {
            $databaseProvider = $container->get(DatabaseProviderInterface::class);
            $database = $databaseProvider->database();
            $tableSchemas = $database->getTables();

            $tables = [];
            foreach ($tableSchemas as $schema) {
                $records = $database->select()->from($schema->getName())->count();
                $tables[] = [
                    'table' => $schema->getName(),
                    'primaryKeys' => $schema->getPrimaryKeys(),
                    'columns' => $this->serializeCycleColumnsSchemas($schema->getColumns()),
                    'records' => $records,
                ];
            }

            return $this->responseFactory->createResponse($tables);
        }

        if ($container->has(ConnectionInterface::class)) {
            $connection = $container->get(ConnectionInterface::class);
            /** @var TableSchemaInterface[] $tableSchemas */
            $tableSchemas = $connection->getSchema()->getTableSchemas();

            $tables = [];
            foreach ($tableSchemas as $schema) {
                $activeQuery = $arFactory->createQueryTo(Common::class, $schema->getName());

                /**
                 * @var Common[] $records
                 */
                $records = $activeQuery->count();

                $tables[] = [
                    'table' => $schema->getName(),
                    'primaryKeys' => $schema->getPrimaryKey(),
                    'columns' => $this->serializeARColumnsSchemas($schema->getColumns()),
                    'records' => $records,
                ];
            }

            return $this->responseFactory->createResponse($tables);
        }

        throw new LogicException(sprintf(
            'Inspecting database is not available. Configure "%s" service to be able to inspect database.',
            ConnectionInterface::class,
        ));
    }

    public function getTable(
        ContainerInterface $container,
        ActiveRecordFactory $arFactory,
        CurrentRoute $currentRoute,
    ): ResponseInterface {
        $tableName = $currentRoute->getArgument('name');

        if ($container->has(DatabaseProviderInterface::class)) {
            $databaseProvider = $container->get(DatabaseProviderInterface::class);
            $database = $databaseProvider->database();
            $schema = $database->table($tableName);

            $result = [
                'table' => $schema->getName(),
                'primaryKeys' => $schema->getPrimaryKeys(),
                'columns' => $this->serializeCycleColumnsSchemas($schema->getColumns()),
                'records' => $database->select()->from($tableName)->fetchAll(),
            ];

            return $this->responseFactory->createResponse($result);
        }

        if ($container->has(ConnectionInterface::class)) {
            $connection = $container->get(ConnectionInterface::class);
            /** @var TableSchemaInterface[] $tableSchemas */
            $schema = $connection->getSchema()->getTableSchema($tableName);

            $activeQuery = $arFactory->createQueryTo(Common::class, $schema->getName());

            /**
             * @var Common[] $records
             */
            $records = $activeQuery->all();

            $data = [];
            // TODO: add pagination
            foreach ($records as $n => $record) {
                foreach ($record->attributes() as $attribute) {
                    $data[$n][$attribute] = $record->{$attribute};
                }
            }

            $result = [
                'table' => $schema->getName(),
                'primaryKeys' => $schema->getPrimaryKey(),
                'columns' => $this->serializeARColumnsSchemas($schema->getColumns()),
                'records' => $data,
            ];

            return $this->responseFactory->createResponse($result);
        }

        throw new LogicException(sprintf(
            'Inspecting database is not available. Configure "%s" service to be able to inspect database.',
            ConnectionInterface::class,
        ));
    }

    public function request(ServerRequestInterface $request, CollectorRepositoryInterface $collectorRepository): ResponseInterface
    {
        $request = $request->getQueryParams();
        $debugEntryId = $request['debugEntryId'] ?? null;

        $data = $collectorRepository->getDetail($debugEntryId);
        $rawRequest = $data[RequestCollector::class]['requestRaw'];

        $request = Message::parseRequest($rawRequest);

        $client = new Client();
        $response = $client->send($request);

        $result = VarDumper::create($response)->asPrimitives();

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

    /**
     * @param ColumnSchemaInterface[] $columns
     */
    private function serializeARColumnsSchemas(array $columns): array
    {
        $result = [];
        foreach ($columns as $columnSchema) {
            $result[] = [
                'name' => $columnSchema->getName(),
                'size' => $columnSchema->getSize(),
                'type' => $columnSchema->getType(),
                'dbType' => $columnSchema->getDbType(),
                'defaultValue' => $columnSchema->getDefaultValue(),
                'comment' => $columnSchema->getComment(),
                'allowNull' => $columnSchema->isAllowNull(),
            ];
        }
        return $result;
    }

    /**
     * @param ColumnInterface[] $columns
     */
    private function serializeCycleColumnsSchemas(array $columns): array
    {
        $result = [];
        foreach ($columns as $columnSchema) {
            $result[] = [
                'name' => $columnSchema->getName(),
                'size' => $columnSchema->getSize(),
                'type' => $columnSchema->getInternalType(),
                'dbType' => $columnSchema->getType(),
                'defaultValue' => $columnSchema->getDefaultValue(),
                'comment' => null, // unsupported for now
                'allowNull' => $columnSchema->isNullable(),
            ];
        }
        return $result;
    }
}
