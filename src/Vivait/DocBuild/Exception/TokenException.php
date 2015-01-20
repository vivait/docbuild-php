<?php

namespace Vivait\DocBuild\Exception;

class TokenException extends HttpException{
    public function __construct($message = null, $code = 401)
    {
        parent::__construct($message, $code);
    }
}