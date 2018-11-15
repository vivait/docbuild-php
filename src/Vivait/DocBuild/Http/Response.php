<?php

namespace Vivait\DocBuild\Http;

class Response
{

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @param int      $statusCode
     * @param resource $stream
     */
    public function __construct(int $statusCode, $stream)
    {
        if ( ! \is_resource($stream) || \get_resource_type($stream) !== 'stream') {
            throw new \InvalidArgumentException("Responses can only be constructed with streams.");
        }

        $this->stream = $stream;
        $this->statusCode = $statusCode;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return \stream_get_contents($this->stream);
    }

    /**
     * @return array
     */
    public function toJsonArray(): array
    {
        return \json_decode($this->toString(), true);
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        return $this->stream;
    }
}
