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
     * @return array
     */
    public function get($resource, $request = [], $headers = []);

    /**
     * @param $resource
     * @param array $request
     * @param array $headers
     * @return array
     */
    public function post($resource, $request = [], $headers = []);

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