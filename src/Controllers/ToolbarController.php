<?php

namespace Yiisoft\Yii\Debug\Viewer\Controllers;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\View\ViewContextInterface;

class ToolbarController implements ViewContextInterface
{
    public function getViewPath(): string
    {
        return dirname(__DIR__, 2) . '/views';
    }

    public function view(ServerRequestInterface $request)
    {
        $id = $request->getAttribute('id');

        return new Response(200, [], $id);
    }
}