<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Debug\DebuggerIdGenerator;

final class Debugger implements MiddlewareInterface
{
    private DebuggerIdGenerator $idGenerator;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(DebuggerIdGenerator $idGenerator, UrlGeneratorInterface $urlGenerator)
    {
        $this->idGenerator = $idGenerator;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $link = $this->urlGenerator->generate('debug/view', ['id' => $this->idGenerator->getId()]);

        return $response
            ->withHeader('X-Debug-Id', $this->idGenerator->getId())
            ->withHeader('X-Debug-Link', $link);
    }
}
