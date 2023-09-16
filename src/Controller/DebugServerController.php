<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Http\Header;
use Yiisoft\Yii\Debug\Api\ServerSentEventsStream;
use Yiisoft\Yii\Debug\DebugServer\Connection;

final class DebugServerController
{
    public function stream(
        ResponseFactoryInterface $responseFactory
    ): ResponseInterface {
        if (\function_exists('pcntl_signal')) {
            \pcntl_signal(\SIGINT, static function (): void {
                exit(1);
            });
        }

        $socket = Connection::create();
        $socket->bind();

        return $responseFactory->createResponse()
            ->withHeader(Header::CONTENT_TYPE, 'text/event-stream')
            ->withHeader(Header::CACHE_CONTROL, 'no-cache')
            ->withHeader(Header::CONNECTION, 'keep-alive')
            ->withBody(
                new ServerSentEventsStream(function () use ($socket) {
                    foreach ($socket->read() as $message) {
                        switch ($message[0]) {
                            case Connection::TYPE_ERROR:
                                return '';
                            default:
                                /**
                                 * Break the loop if the client aborted the connection (closed the page)
                                 */
                                if (connection_aborted()) {
                                    return $message[1];
                                }
                                yield $message[1];
                        }
                    }
                    return '';
                })
            );
    }
}
