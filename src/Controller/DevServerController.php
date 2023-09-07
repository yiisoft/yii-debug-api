<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Aliases\Aliases;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Yii\Debug\Api\ServerSentEventsStream;
use Yiisoft\Yii\Debug\DevServer\Connection;

final class DevServerController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private Aliases $aliases,
    ) {
    }

    public function stream(
        ResponseFactoryInterface $responseFactory
    ): ResponseInterface {
        $maxRetries = 1;
        $retries = 0;

        if (\function_exists('pcntl_signal')) {
            \pcntl_signal(\SIGINT, static function (): void {
                exit(1);
            });
        }

        return $responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withBody(
                new ServerSentEventsStream(function (array &$buffer) use (
                    &$hash,
                    &$retries,
                    $maxRetries,
                ) {
                    $socket = Connection::create();
                    $socket->bind();
                    $messages = $socket->read(
                        fn (string $data) => $data,
                        fn () => yield 0x001,
                    );
                    foreach ($messages as $message) {
                        if ($message === 0x001) {
                            return true;
                        }
                        $buffer[] = $message;

                        // break the loop if the client aborted the connection (closed the page)
                        if (connection_aborted()) {
                            return true;
                        }
                        if ($retries++ >= $maxRetries) {
                            return true;
                        }

                        sleep(1);
                        return true;
                    }
                })
            );
    }
}
