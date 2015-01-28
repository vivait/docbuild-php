<?php

namespace Vivait\DocBuild\Exception;

class BadCredentialsException extends \InvalidArgumentException
{
    public function __construct($message = 'You must provide a client ID and a client secret')
    {
        parent::__construct($message);
    }
}