<?php

namespace Vivait\DocBuild\Exception;

use RuntimeException;
use Throwable;

class FileException extends RuntimeException
{

    /**
     * @param Throwable|null $previous
     */
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('Not a valid stream resource', 0, $previous);
    }
}
