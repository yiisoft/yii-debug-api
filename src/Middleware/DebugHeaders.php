<?php

namespace Yiisoft\Yii\Debug\Viewer\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Debug\Debugger;

class DebugHeaders implements MiddlewareInterface
{
    private Debugger $debugger;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(Debugger $debugger, UrlGeneratorInterface $urlGenerator)
    {
        $this->debugger = $debugger;
        $this->urlGenerator = $urlGenerator;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $url = $this->urlGenerator->generate('debugger.view', ['id' => $this->debugger->getId()]);

        return $handler->handle($request)
            ->withHeader('X-Debug-Tag', $this->debugger->getId())
            ->withHeader(
                'X-Debug-Duration',
                // TODO implement
                'number_format((microtime(true) - YII_BEGIN_TIME) * 1000 + 1)'
            )
            ->withHeader('X-Debug-Link', $url);
    }
}
