<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Repository;

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
            return $data[$id] ?? [];
        }

        return $data;
    }

    public function getDetail(string $id): array
    {
        $data = $this->loadData();
        return $data[$id] ?? [];
    }

    private function loadIndexData(): array
    {
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
        $dataFiles = \glob($this->path . '/yii-debug*.data.json', GLOB_NOSORT);
        $data = [];
        foreach ($dataFiles as $file) {
            $id = \basename($file, '.data.json');
            $data[$id] = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }
}
