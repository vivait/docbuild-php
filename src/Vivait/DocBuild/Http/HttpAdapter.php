<?php

namespace Vivait\DocBuild\Http;


interface HttpAdapter
{
    const TOKEN_EXPIRED = 'The access token provided has expired.';
    const TOKEN_INVALID = 'The access token provided is invalid.';

    /**
     * @param $url
     * @return self
     */
    public function setUrl($url);

    /**
     * @param $resource
     * @param array $request
     * @param array $headers
     * @param bool $json
     * @return array|string
     */
    public function get($resource, $request = [], $headers = [], $json = true);

    /**
     * @param $resource
     * @param array $request
     * @param array $headers
     * @param bool $json
     * @return array|string
     */
    public function post($resource, $request = [], $headers = [], $json = true);

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