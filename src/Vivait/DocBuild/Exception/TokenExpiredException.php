<?php

namespace Vivait\DocBuild\Exception;

use Vivait\DocBuild\Http\HttpAdapter;

class TokenExpiredException extends UnauthorizedException
{
    public function __construct($message = HttpAdapter::TOKEN_EXPIRED)
    {
        parent::__construct($message);
    }
}