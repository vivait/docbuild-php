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

    private $accessToken;

    /**
     * @param HttpAdapter $http
     */
    public function __construct(HttpAdapter $http)
    {
        $this->http = $http;
    }

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
            $this->accessToken = $response['access_token'];
        } elseif ($code == 400 || $code == 401 || $code == 403) {
            throw new UnauthorizedException(json_encode($response), $code);
        } else {
            throw new HttpException(json_encode($response), $code);
        }
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function hasAccessToken()
    {
        return (boolean) $this->accessToken;
    }
}