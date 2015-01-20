<?php

namespace Vivait\DocBuild;

use Vivait\DocBuild\Exception\BadRequestException;
use Vivait\DocBuild\Exception\EmptyTokenException;
use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\GuzzleAdapter;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuild
{
    const URL = "http://doc.build/api";
    /**
     * @var HttpAdapter
     */
    private $http;

    /**
     * @var string
     */
    private $token;

    /**
     * @param HttpAdapter $http
     * @internal param $key
     */
    public function __construct(HttpAdapter $http = null)
    {
        if (!$http) {
            $this->http = new GuzzleAdapter();
        } else {
            $this->http = $http;
        }

        $this->http->setUrl(self::URL);
    }

    public function createDocument($name, $extension, $file = null)
    {
        //TODO
    }

    public function uploadDocument($id, $file)
    {
        //TODO
    }

    public function getDocuments()
    {
        $this->checkToken();

        return $this->get('documents');
    }

    public function getDocument($id)
    {
        $this->checkToken();
        return $this->get('documents/' . $id);
    }

    public function downloadDocument($id)
    {
        //TODO think about how binary data will be handled
        return $this->get('documents/' . $id . '/payload');
    }

    public function createCallback($source, $url)
    {
        //TODO
    }

    public function combineDocument($name, array $source = [], $callback)
    {
        //TODO
    }

    public function convertToPdf($source, $callback)
    {
        //TODO
    }

    private function get($resource, array $request = [], array $headers = [], $tokenRequired = true)
    {
        if ($tokenRequired) {
            $this->checkToken();
            $request['access_token'] = $this->token;
        }

        return $this->http->get($resource, $request, $headers);
    }

    private function post($resource, array $request = [], array $headers = [], $tokenRequired = true)
    {
        if ($tokenRequired) {
            $this->checkToken();
            $request['access_token'] = $this->token;
        }

        return $this->http->post($resource, $request, $headers);
    }

    public function getHttpAdapter()
    {
        return $this->http;
    }

    /**
     * @param $clientId
     * @param $clientSecret
     * @return string
     */
    public function authorise($clientId, $clientSecret)
    {
        $response = $this->http->get(
            'oauth/token',
            ['client_id' => $clientId, 'client_secret' => $clientSecret, 'grant_type' => 'client-credentials']
        );

        $code = $this->http->getResponseCode();

        if ($code == 200 && array_key_exists('access_token', $response)) {
            $this->token = $response['access_token'];

            return $this->token;
        } elseif ($code == 400 || $code == 401 || $code == 403) {
            throw new UnauthorizedException(json_encode($response), $code);
        } else {
            throw new HttpException(json_encode($response), $code);
        }
    }

    /**
     * @param $clientId
     * @param $clientSecret
     * @param $grantType
     * @return string
     */
    public function authorize($clientId, $clientSecret, $grantType = 'client_credentials')
    {
        return $this->authorise($clientId, $clientSecret, $grantType);
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    public function checkToken()
    {
        if (!$this->token) {
            throw new EmptyTokenException('You must set a token. Do you need to authorize?');
        }
    }
}
