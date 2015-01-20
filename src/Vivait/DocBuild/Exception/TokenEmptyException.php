<?php

namespace Vivait\DocBuild\Exception;

class TokenEmptyException extends \InvalidArgumentException
{
    public function __construct($message = 'You must provide an access token')
    {
        parent::__construct($message);
    }
}