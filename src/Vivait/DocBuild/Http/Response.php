<?php

namespace Vivait\DocBuild\Http;

use InvalidArgumentException;

use function fclose;
use function fopen;
use function get_resource_type;
use function is_resource;
use function json_decode;
use function rewind;
use function stream_copy_to_stream;
use function stream_get_contents;

class Response
{

    /**
     * @var resource
     */
    private $stream;

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @param int      $statusCode
     * @param resource $stream
     */
    public function __construct(int $statusCode, $stream)
    {
        if ( ! is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException("Responses can only be constructed with streams.");
        }

        // Copy the stream to an in-memory one that was opened so that we can rewind it automatically on each method
        // call
        $memoryStream = fopen('php://memory', 'r+');

        stream_copy_to_stream($stream, $memoryStream);

        $this->stream = $memoryStream;
        $this->statusCode = $statusCode;

        // Close the original stream
        fclose($stream);

        $this->rewindStream();
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
        $this->rewindStream();

        return stream_get_contents($this->stream);
    }

    /**
     * @return array|null
     */
    public function toJsonArray(): ?array
    {
        $this->rewindStream();

        $data = $this->toString();
        return json_decode($data, true);
    }

    /**
     * @return resource
     */
    public function getStream()
    {
        $this->rewindStream();

        return $this->stream;
    }

    private function rewindStream(): void
    {
        rewind($this->stream);
    }
}
