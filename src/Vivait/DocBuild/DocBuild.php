<?php

namespace Vivait\DocBuild;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Vivait\DocBuild\Exception\BadCredentialsException;

use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Exception\TokenEmptyException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\GuzzleAdapter;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuild
{
    const URL = "http://doc.build/api";
    protected $optionsResolver;
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

    private $tokenRefreshes = 0;

    protected $options;

    /**
     * @param null $clientId
     * @param null $clientSecret
     * @param array $options
     * @param HttpAdapter $http
     * @internal param $key
     */
    public function __construct($clientId = null, $clientSecret = null, array $options = [], HttpAdapter $http = null)
    {
        $this->clientSecret = $clientSecret;
        $this->clientId = $clientId;

        $this->optionsResolver = new OptionsResolver();
        $this->setOptions($options);

        if (!$http) {
            $this->http = new GuzzleAdapter();
        } else {
            $this->http = $http;
        }

        $this->http->setUrl(self::URL);
    }

    public function setOptions(array $options = [])
    {
        $this->configureOptions($this->optionsResolver);
        $this->options = $this->optionsResolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'token_refresh' => false,
            'max_token_refresh' => 5,
        ]);
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

    private function get($resource, array $request = [], array $headers = [])
    {
        $this->checkToken();
        $request['access_token'] = $this->token;

        if ($this->options['token_refresh'] && $this->tokenRefreshes < $this->options['max_token_refresh']) {
            try {
                return $this->http->get($resource, $request, $headers);
            } catch (TokenExpiredException $e) {
                var_dump(true);
                $this->authorize($this->clientId, $this->clientSecret);
                $this->get($resource, $request, $headers);
            }
        } else {
            return $this->http->get($resource, $request, $headers);
        }
    }

    private function post($resource, array $request = [], array $headers = [])
    {
        $this->checkToken();
        $response = $this->http->post($resource, $request, $headers);

        return $response;
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

    public function getHttpAdapter()
    {
        return $this->http;
    }

}
