<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Debug\Repository;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Api\Debug\Repository\CollectorRepository;
use Yiisoft\Yii\Debug\Api\Tests\Support\StubCollector;
use Yiisoft\Yii\Debug\DebuggerIdGenerator;
use Yiisoft\Yii\Debug\Storage\MemoryStorage;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

final class CollectorRepositoryTest extends TestCase
{
    public function testSummary(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $stubCollector = new StubCollector(['key' => 'value']);

        $storage = $this->createStorage($idGenerator);
        $repository = new CollectorRepository($storage);

        $this->assertIsArray($repository->getSummary());
        $this->assertEquals([
            [
                'id' => $idGenerator->getId(),
                'collectors' => [],
            ],
        ], $repository->getSummary());

        $storage->addCollector($stubCollector);

        $this->assertIsArray($repository->getSummary());
        $this->assertEquals([
            [
                'id' => $idGenerator->getId(),
                'collectors' => [$stubCollector->getName()],
            ],
        ], $repository->getSummary());
    }

    public function testDetail(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $stubCollector = new StubCollector(['key' => 'value']);

        $storage = $this->createStorage($idGenerator);
        $storage->addCollector($stubCollector);

        $repository = new CollectorRepository($storage);

        $this->assertIsArray($repository->getDetail($idGenerator->getId()));
        $this->assertEquals([
            $stubCollector->getName() => $stubCollector->getCollected(),
        ], $repository->getDetail($idGenerator->getId()));
    }

    public function testDumpObject(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $stubCollector = new StubCollector(['key' => 'value']);

        $storage = $this->createStorage($idGenerator);
        $storage->addCollector($stubCollector);

        $repository = new CollectorRepository($storage);

        $this->assertIsArray($repository->getDumpObject($idGenerator->getId()));
        $this->assertEquals([
            'key' => 'value',
        ], $repository->getDumpObject($idGenerator->getId()));
    }

    public function testObject(): void
    {
        $idGenerator = new DebuggerIdGenerator();

        $objectId = '123';
        $stubCollector = new StubCollector([
            'stdClass#' . $objectId => 'value',
        ]);

        $storage = $this->createStorage($idGenerator);
        $storage->addCollector($stubCollector);

        $repository = new CollectorRepository($storage);

        $this->assertIsArray($repository->getObject($idGenerator->getId(), $objectId));
        $this->assertEquals([
            'stdClass',
            'value',
        ], $repository->getObject($idGenerator->getId(), $objectId));
    }

    private function createStorage(DebuggerIdGenerator $idGenerator): StorageInterface
    {
        return new MemoryStorage($idGenerator);
    }
}
