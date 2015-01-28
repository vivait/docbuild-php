<?php

namespace Vivait\DocBuild\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct($message = 'You do not have access to this resource', $code = 401)
    {
        parent::__construct($message, $code);
    }
}