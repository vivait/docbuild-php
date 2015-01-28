<?php

namespace Vivait\DocBuild\Exception;


class AdapterException extends \RuntimeException
{
    public function __construct($message = 'The HttpAdapter thew an exception')
    {
        parent::__construct($message);
    }
}