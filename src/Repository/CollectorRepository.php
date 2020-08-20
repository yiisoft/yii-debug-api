<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Repository;

use Yiisoft\Json\Json;
use Yiisoft\Yii\Debug\Api\Exception\NotFoundException;

class CollectorRepository implements CollectorRepositoryInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getSummary(?string $id = null): array
    {
        $data = $this->loadIndexData();
        if ($id !== null) {
            if (isset($data[$id])) {
                return $data[$id];
            }

            throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
        }

        return array_values($data);
    }

    public function getDetail(string $id, string $collector): array
    {
        $data = $this->loadData();
        if (!isset($data[$id])) {
            throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
        }

        if (!isset($data[$id][$collector])) {
            throw new NotFoundException(sprintf('Unable to find debug data collected with "%s"', $collector));
        }

        return $data[$id][$collector];
    }

    private function loadIndexData(): array
    {
        clearstatcache();
        $dataFiles = \glob($this->path . '/yii-debug*.index.json', GLOB_NOSORT);
        $data = [];
        foreach ($dataFiles as $file) {
            $id = \basename($file, '.index.json');
            $data[$id] = Json::decode(file_get_contents($file));
        }

        return $data;
    }

    private function loadData(): array
    {
        clearstatcache();
        $dataFiles = \glob($this->path . '/yii-debug*.data.json', GLOB_NOSORT);
        $data = [];
        foreach ($dataFiles as $file) {
            $data = Json::decode(file_get_contents($file));
        }

        return $data;
    }
}
