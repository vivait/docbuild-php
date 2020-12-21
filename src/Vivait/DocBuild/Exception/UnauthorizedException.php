<?php

namespace Vivait\DocBuild\Exception;

use RuntimeException;

class UnauthorizedException extends RuntimeException
{

    /**
     * @param string $message The exception message.
     * @param int    $code    The exception code.
     */
    public function __construct($message = 'You do not have access to this resource', $code = 401)
    {
        parent::__construct($message, $code);
    }
}
