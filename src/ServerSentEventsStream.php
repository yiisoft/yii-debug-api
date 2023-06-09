<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api;

use Closure;
use Psr\Http\Message\StreamInterface;

final class ServerSentEventsStream implements StreamInterface, \Stringable
{
    public array $buffer = [];
    private bool $eof = false;

    public function __construct(
        private Closure $stream,
    ) {
    }

    public function __toString(): string
    {
        return '';
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

    public function tell()
    {
        // TODO: Implement tell() method.
    }

    public function eof()
    {
        return $this->eof;
    }

    public function isSeekable(): bool
    {
        return false;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function rewind()
    {
        throw new \RuntimeException('Stream is not seekable');
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string)
    {
        throw new \RuntimeException('Stream is not writable');
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        $continue = ($this->stream)($this->buffer);

        if (!$continue) {
            $this->eof = true;
        }

        $output = '';
        foreach ($this->buffer as $key => $value) {
            unset($this->buffer[$key]);
            $output .= sprintf("data: %s\n", $value);
        }
        $output .= "\n";
        return $output;
    }

    public function getContents()
    {
        // TODO: Implement getContents() method.
    }

    public function getMetadata($key = null): array
    {
        return [];
    }
}
