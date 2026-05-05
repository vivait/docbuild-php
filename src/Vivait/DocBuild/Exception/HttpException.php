<?php

namespace Vivait\DocBuild\Exception;

use RuntimeException;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpException extends RuntimeException
{

    private ?ResponseInterface $response;

    public function __construct(mixed $code, string $message, ?ResponseInterface $response = null)
    {
        parent::__construct(sprintf("There was an issue with the HTTP request or response: %s", $message), $code);

        $this->response = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }
}
