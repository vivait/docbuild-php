<?php

namespace Vivait\DocBuild\Exception;


class InvalidTokenException extends TokenException
{
    public function __construct($message = null, $code = 401)
    {
        parent::__construct($message, $code);
    }
}