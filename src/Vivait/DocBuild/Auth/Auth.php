<?php

namespace Vivait\DocBuild\Auth;

use Vivait\DocBuild\Exception\BadCredentialsException;
use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\HttpAdapter;

class Auth
{
    /**
     * @var HttpAdapter
     */
    private $http;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @param HttpAdapter $http
     */
    public function __construct(HttpAdapter $http)
    {
        $this->http = $http;
    }

    /**
     * @param $clientId
     * @param $clientSecret
     * @return string
     */
    public function authorize($clientId, $clientSecret)
    {
        if(!$clientId && !$clientSecret){
            throw new BadCredentialsException();
        }

        $response = $this->http->get(
            'oauth/token',
            ['client_id' => $clientId, 'client_secret' => $clientSecret, 'grant_type' => 'client_credentials']
        );

        $code = $this->http->getResponseCode();

        if ($code == 200 && array_key_exists('access_token', $response)) {
            $this->setAccessToken($response['access_token']);
            return $this->getAccessToken();
        } elseif ($code == 400 || $code == 401 || $code == 403) {
            throw new UnauthorizedException(json_encode($response), $code);
        } else {
            throw new HttpException(json_encode($response), $code);
        }
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return bool
     */
    public function hasAccessToken()
    {
        return (boolean) $this->accessToken;
    }
}