<?php

namespace Vivait\DocBuild\Exception;

class FileException extends \RuntimeException
{

    /**
     * @param \Exception|null $previous
     */
    public function __construct(\Exception $previous = null)
    {
        parent::__construct('Not a valid stream resource', null, $previous);
    }
}
