<?php

namespace Vivait\DocBuild\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct($message, $code = 401)
    {
        parent::__construct($message, $code);
    }
}