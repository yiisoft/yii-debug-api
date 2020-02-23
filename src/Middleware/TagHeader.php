<?php

namespace Yiisoft\Yii\Debug\Viewer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Yii\Debug\Debugger;

class TagHeader implements MiddlewareInterface
{
    private Debugger $debugger;

    public function __construct(Debugger $debugger)
    {
        $this->debugger = $debugger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response = $response->withHeader('X-Debug-Tag', $this->debugger->getId());
        $response = $response->withHeader(
            'X-Debug-Duration',
            'number_format((microtime(true) - YII_BEGIN_TIME) * 1000 + 1)'
        );
        $response = $response->withHeader('X-Debug-Link', '$url');

        return $response;
    }
}
