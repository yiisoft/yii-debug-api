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
        $data = $this->loadData('.index.json', $id);
        if ($id !== null) {
            return $data;
        }

        return array_values($data);
    }

    public function getDetail(string $id, ?string $collector = null): array
    {
        $data = $this->loadData('.data.json', $id);

        if (empty($collector)) {
            return $data;
        }

        if (!isset($data[$collector])) {
            throw new NotFoundException(sprintf('Unable to find debug data collected with "%s"', $collector));
        }

        return $data[$collector];
    }

    public function getDumpObject(string $id, ?string $collector): array
    {
        $data = $this->loadData('.obj.json', $id);

        if (empty($collector)) {
            return $data;
        }

        if (!isset($data[$collector])) {
            throw new NotFoundException(sprintf('Unable to find debug data collected with "%s"', $collector));
        }

        return $data[$collector];
    }

    private function loadData(string $fileSuffix, ?string $id = null): array
    {
        clearstatcache();
        if (!empty($id)) {
            $file = $this->path . DIRECTORY_SEPARATOR . $id . $fileSuffix;
            if (!file_exists($file) || is_dir($file)) {
                throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
            }

            return Json::decode(file_get_contents($file));
        }

        $data = [];
        $dataFiles = \glob($this->path . '/yii-debug*' . $fileSuffix, GLOB_NOSORT);
        foreach ($dataFiles as $file) {
            $id = \basename($file, $fileSuffix);
            $data[$id] = Json::decode(file_get_contents($file));
        }

        return $data;
    }
}
