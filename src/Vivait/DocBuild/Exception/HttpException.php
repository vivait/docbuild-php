<?php

namespace Vivait\DocBuild\Exception;

use RuntimeException;
use Vivait\DocBuild\Http\Response;

use function sprintf;

class HttpException extends RuntimeException
{

    /**
     * @var null|Response
     */
    private ?Response $response;

    /**
     * @param int           $code
     * @param string        $message
     * @param null|Response $response
     */
    public function __construct(int $code, string $message, ?Response $response = null)
    {
        parent::__construct(sprintf("There was an issue with the HTTP request or response: %s", $message), $code);

        $this->response = $response;
    }

    /**
     * @return null|Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
