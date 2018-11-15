<?php

namespace Vivait\DocBuild\Exception;

use Vivait\DocBuild\DocBuild;

class TokenInvalidException extends UnauthorizedException
{

    /**
     * @param string $message The exception message.
     */
    public function __construct($message = DocBuild::TOKEN_INVALID)
    {
        parent::__construct($message);
    }
}
