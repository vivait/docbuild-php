<?php

namespace Vivait\DocBuild\Model\Exception;

use Vivait\DocBuild\Http\Response;

class HttpException extends \RuntimeException
{

    /**
     * @var null|Response
     */
    private $response;

    /**
     * @param int           $code
     * @param null|Response $response
     */
    public function __construct(int $code, ?Response $response = null)
    {
        parent::__construct("There was an issue with the HTTP request or response.", $code);

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
