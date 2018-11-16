<?php

namespace Vivait\DocBuild\Exception;

use Vivait\DocBuild\DocBuild;

class TokenExpiredException extends UnauthorizedException
{

    /**
     * @param string $message The exception message.
     */
    public function __construct($message = DocBuild::TOKEN_EXPIRED)
    {
        parent::__construct($message);
    }
}
