<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Repository;

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
        if (isset($data[$id])) {
            $data = $data[$id];
        } else {
            throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
        }

        if (isset($data[$collector])) {
            $data = $data[$collector];
        } else {
            throw new NotFoundException(sprintf('Unable to find debug data collected with "%s"', $collector));
        }
        return $data;
    }

    private function loadIndexData(): array
    {
        clearstatcache();
        $dataFiles = \glob($this->path . '/yii-debug*.index.json', GLOB_NOSORT);
        $data = [];
        foreach ($dataFiles as $file) {
            $id = \basename($file, '.index.json');
            $data[$id] = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    private function loadData(): array
    {
        clearstatcache();
        $dataFiles = \glob($this->path . '/yii-debug*.data.json', GLOB_NOSORT);
        $data = [];
        foreach ($dataFiles as $file) {
            $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }
}
