<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api;

use Closure;
use Generator;
use Psr\Http\Message\StreamInterface;

final class ServerSentEventsStream implements StreamInterface, \Stringable
{
    private bool $eof = false;

    /**
     * @param Closure(): Generator $stream
     */
    public function __construct(
        private Closure $stream,
    ) {
    }

    public function close(): void
    {
        $this->eof = true;
    }

    public function detach(): void
    {
        $this->eof = true;
    }

    public function getSize()
    {
        return null;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return $this->eof;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function rewind(): void
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): void
    {
        throw new \RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    /**
     * TODO: support length reading
     */
    public function read(int $length): string
    {
        foreach (($this->stream)($this) as $message) {
            if (empty($message)) {
                break;
            }

            return sprintf("data: %s\n\n", $message);
        }
        $this->eof = true;
        return '';
    }

    public function getContents(): string
    {
        return $this->read(8_388_608); // 8MB
    }

    public function getMetadata($key = null): array
    {
        return [];
    }

    public function __toString(): string
    {
        return $this->getContents();
    }
}
