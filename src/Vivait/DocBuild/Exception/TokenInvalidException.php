<?php

namespace Vivait\DocBuild\Exception;


use Vivait\DocBuild\Http\HttpAdapter;

class TokenInvalidException extends UnauthorizedException
{
    public function __construct($message = HttpAdapter::TOKEN_INVALID)
    {
        parent::__construct($message);
    }
}