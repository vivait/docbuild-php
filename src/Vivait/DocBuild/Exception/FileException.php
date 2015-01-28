<?php

namespace Vivait\DocBuild\Exception;


class FileException extends \RuntimeException
{
    public function __construct(\Exception $previous = null)
    {
        parent::__construct('The file you are trying to upload is invalid. Check its path.', null, $previous);
    }
}