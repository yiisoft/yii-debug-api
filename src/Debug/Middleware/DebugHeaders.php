<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\Debug\Debugger;

/**
 * Adds debug headers to response. Information from these headers may be used to request information about
 * the current request as it is done in the debug toolbar.
 */
final class DebugHeaders implements MiddlewareInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private ?Debugger $debugger = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($this->debugger === null || !$this->debugger->isActive()) {
            return $response;
        }

        $link = $this->urlGenerator->generate('debug/api/view', ['id' => $this->debugger->getId()]);

        return $response
            ->withHeader('X-Debug-Id', $this->debugger->getId())
            ->withHeader('X-Debug-Link', $link);
    }
}
