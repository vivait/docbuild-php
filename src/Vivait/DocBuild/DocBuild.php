<?php

namespace Vivait\DocBuild;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Vivait\DocBuild\Auth\Auth;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Http\GuzzleAdapter;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuild
{
    const URL = "http://doc.build/api/";

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

    protected $options;

    private $tokenRefreshes;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @param null $clientId
     * @param null $clientSecret
     * @param array $options
     * @param HttpAdapter $http
     * @param Auth $auth
     */
    public function __construct($clientId = null, $clientSecret = null, array $options = [], HttpAdapter $http = null, Auth $auth = null)
    {
        $this->clientSecret = $clientSecret;
        $this->clientId = $clientId;

        $this->optionsResolver = new OptionsResolver();
        $this->setOptions($options);

        if(!$this->http = $http){
            $this->http = new GuzzleAdapter();
        }

        $this->http->setUrl(self::URL);

        if(!$this->auth = $auth){
            $this->auth = new Auth($this->http);
        }
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

    /**
     * @param $resource
     * @param array $request
     * @param array $headers
     * @return array
     */
    private function get($resource, array $request = [], array $headers = [])
    {
        if(!$this->auth->hasAccessToken()){
            $this->auth->authorize($this->clientId, $this->clientSecret);
        }

        try {
            $request['access_token'] = $this->auth->getAccessToken();
            return $this->http->get($resource, $request, $headers);
        } catch (TokenExpiredException $e) {
            if ($this->options['token_refresh'] &&  $this->tokenRefreshes < $this->options['max_token_refresh']) {
                $this->auth->authorize($this->clientId, $this->clientSecret);
                $this->tokenRefreshes++;
                return $this->get($resource, $request, $headers);
            }
            else{
                $this->tokenRefreshes = 0;
                throw $e;
            }
        }
    }

    public function getAuth()
    {
        return $this->auth;
    }

    private function post($resource, array $request = [], array $headers = [])
    {

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
