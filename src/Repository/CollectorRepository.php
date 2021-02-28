<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Repository;

use Yiisoft\Yii\Debug\Api\Exception\NotFoundException;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

class CollectorRepository implements CollectorRepositoryInterface
{
    private StorageInterface $storage;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function getSummary(?string $id = null): array
    {
        $data = $this->loadData(StorageInterface::TYPE_INDEX, $id);
        if ($id !== null) {
            return $data;
        }

        return array_values(array_reverse($data));
    }

    public function getDetail(string $id): array
    {
        return $this->loadData(StorageInterface::TYPE_DATA, $id);
    }

    public function getDumpObject(string $id): array
    {
        return $this->loadData(StorageInterface::TYPE_OBJECTS, $id);
    }

    public function getObject(string $id, string $objectId)
    {
        $dump = $this->loadData(StorageInterface::TYPE_OBJECTS, $id);

        foreach ($dump as $name => $value) {
            if (strrpos($name, "#$objectId") !== false) {
                return $value;
            }
        }

        return null;
    }

    private function loadData(string $fileType, ?string $id = null): array
    {
        $data = $this->storage->read($fileType);
        if (!empty($id)) {
            if (!isset($data[$id])) {
                throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
            }

            return $data[$id];
        }

        return $data;
    }
}
