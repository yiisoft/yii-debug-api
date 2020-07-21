<?php

namespace Yiisoft\Yii\Debug\Api\Controller;

/**
 * Debugger controller provides browsing over available debug logs.
 */
class DefaultController
{
    /**
     * @var array
     */
    private array $manifest;

    /**
     * Index action
     *
     * @return array
     */
    public function index(): array
    {
        // TODO: implement
        return [];
    }

    /**
     * @param string|null $tag debug data tag.
     * @param string|null $panel debug panel ID.
     * @return array response.
     */
    public function view(string $tag = null, string $panel = null): array
    {
        // TODO: implement
        return [];
    }

    /**
     * Toolbar action
     *
     * @param string $tag
     * @return array
     */
    public function summary($tag)
    {
        // TODO: implement
        return [];
    }

    /**
     * @param bool $forceReload
     */
    protected function getManifest($forceReload = false)
    {
    }
}
