<?php

namespace Vivait\DocBuild\Http;


interface HttpAdapter
{
    /**
     * @param $url
     * @return self
     */
    public function setUrl($url);

    /**
     * @param $key
     * @return self
     */
    public function setKey($key);

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
     * @param $method
     * @param $url
     * @param array $options
     * @return mixed
     */
    public function sendRequest($method, $url, array $options = []);

    /**
     * @return int
     */
    public function getResponseCode();

    /**
     * @return array
     */
    public function getResponseHeaders();
}