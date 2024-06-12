<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

final class DebugFallbackHandler implements MiddlewareInterface, RequestHandlerInterface
{
    private ?RequestHandlerInterface $fallbackHandler = null;

    public function __construct(
        private readonly MiddlewareDispatcher $middlewareDispatcher,
        private readonly array $middlewareDefinitions,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->middlewareDispatcher
            ->withMiddlewares($this->middlewareDefinitions)
            ->dispatch($request, $handler);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->fallbackHandler === null) {
            throw new \RuntimeException('No fallback handler defined.');
        }

        return $this->process($request, $this->fallbackHandler);
    }

    public function withFallbackRequestHandler(RequestHandlerInterface $fallbackHandler): self
    {
        $new = clone $this;
        $new->fallbackHandler = $fallbackHandler;

        return $new;
    }
}
