<?php

namespace Vivait\DocBuild\Http;


interface HttpAdapter
{
    const TOKEN_EXPIRED = 'The access token provided has expired.';
    const TOKEN_INVALID = 'The access token provided is invalid.';

    const RETURN_TYPE_STRING = 10;
    const RETURN_TYPE_JSON = 11;
    const RETURN_TYPE_STREAM = 12;

    /**
     * @param $url
     * @return self
     */
    public function setUrl($url);

    /**
     * @param $resource
     * @param array $request
     * @param array $headers
     * @param int $returnType
     * @return array|mixed|string|resource
     */
    public function get($resource, $request = [], $headers = [], $returnType = self::RETURN_TYPE_JSON);

    /**
     * @param $resource
     * @param array $request
     * @param array $headers
     * @param int $returnType
     * @return array|mixed|string|resource
     */
    public function post($resource, $request = [], $headers = [], $returnType = self::RETURN_TYPE_JSON);

    /**
     * @return int
     */
    public function getResponseCode();

    /**
     * @return array
     */
    public function getResponseHeaders();

    /**
     * @return string
     */
    public function getResponseContent();
}