<?php

namespace Vivait\DocBuild\Http;

use Vivait\DocBuild\Model\Exception\HttpException;

interface Adapter
{

    /**
     * @param string $method  The method to use for the request.
     * @param string $url     The URL to make the request to.
     * @param array  $request The request's data - note that these could be anything (resources, scalar values, arrays).
     * @param array  $headers The request's headers.
     *
     * @throws HttpException
     *
     * @return Response
     */
    public function sendRequest(
        string $method,
        string $url,
        array $request = [],
        array $headers = []
    ): Response;
}
