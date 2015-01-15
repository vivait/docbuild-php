<?php

namespace Vivait\DocBuild\Exception;

class UnauthorizedException extends HttpException
{
    public function __construct($message = null)
    {
        parent::__construct($message, 401);
    }
}