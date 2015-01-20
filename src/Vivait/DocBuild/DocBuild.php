<?php

namespace Vivait\DocBuild;

use Vivait\DocBuild\Exception\BadCredentialsException;
use Vivait\DocBuild\Exception\BadRequestException;
use Vivait\DocBuild\Exception\EmptyTokenException;
use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Exception\TokenEmptyException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\GuzzleAdapter;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuild
{
    const URL = "http://doc.build/api";
    const MAX_AUTH_RETRY = 5;

    /**
     * @var HttpAdapter
     */
    private $http;

    /**
     * @var string
     */
    private $token;

    /**
     * @var
     */
    private $clientSecret;

    /**
     * @var
     */
    private $clientId;

    private $authRetryCount = 0;

    /**
     * @param null $clientId
     * @param null $clientSecret
     * @param HttpAdapter $http
     * @internal param $key
     */
    public function __construct($clientId = null, $clientSecret = null, HttpAdapter $http = null)
    {
        $this->clientSecret = $clientSecret;
        $this->clientId = $clientId;

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
        return $this->get('documents');
    }

    public function getDocument($id)
    {
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

    private function get($resource, array $request = [], array $headers = [], $tokenRequired = true, $retryAuth = true)
    {
        if ($tokenRequired) {
            $this->checkToken();
            $request['access_token'] = $this->token;
        }

        if ($retryAuth) {
            try {
                return $this->http->get($resource, $request, $headers);
            } catch (TokenExpiredException $e) {
                $this->authorize($this->clientId, $this->clientSecret);

                $this->get($resource, $request, $headers, $tokenRequired, $retryAuth);
            }
        } else {
            return $this->http->get($resource, $request, $headers);
        }
    }

    private function post($resource, array $request = [], array $headers = [], $tokenRequired = true)
    {
        if ($tokenRequired) {
            $this->checkToken();
            $request['access_token'] = $this->token;
        }

        $response = $this->http->post($resource, $request, $headers);

        return $response;
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
    public function authorize($clientId = null, $clientSecret = null)
    {
        if($clientId && $clientSecret){
            $this->setClientId($clientId);
            $this->setClientSecret($clientSecret);
        }

        if(!$this->clientId || !$this->clientSecret){
            throw new BadCredentialsException();
        }

        $response = $this->http->get(
            'oauth/token',
            ['client_id' => $this->clientId, 'client_secret' => $this->clientSecret, 'grant_type' => 'client_credentials']
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
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    public function checkToken()
    {
        if (!$this->token) {
            throw new TokenEmptyException();
        }
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

}
