<?php

namespace Vivait\DocBuild\Exception;


class AdapterException extends \RuntimeException
{
    public function __construct(\Exception $previous = null)
    {
        parent::__construct('The HttpAdapter thew an exception', null, $previous);
    }
}