<?php

namespace Vivait\DocBuild\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct($message = null, $code = 401)
    {
        parent::__construct($message, $code);
    }
}