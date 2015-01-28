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

    /**
     * @var OptionsResolver
     */
    protected $optionsResolver;

    /**
     * @var HttpAdapter
     */
    protected $http;

    /**
     * @var string
     */
    protected $clientSecret;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var int
     */
    protected $tokenRefreshes = 0;

    /**
     * @var Auth
     */
    protected $auth;

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
    protected function get($resource, array $request = [], array $headers = [])
    {
        return $this->performRequest('get', $resource, $request, $headers);
    }

    protected function post($resource, array $request = [], array $headers = [])
    {
        return $this->performRequest('post', $resource, $request, $headers);
    }

    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @param $name
     * @param $extension
     * @param null $file
     * @return array|mixed|string
     */
    public function createDocument($name, $extension, $file = null)
    {
        //TODO handle file
        return $this->post('documents', [
            'document[name]' => $name,
            'document[extension]' => $extension
        ]);
    }

    /**
     * @param $id
     * @param $file
     */
    public function uploadDocument($id, $file)
    {
        //TODO
    }

    /**
     * @return array
     */
    public function getDocuments()
    {
        return $this->get('documents');
    }

    /**
     * @param $id
     * @return array
     */
    public function getDocument($id)
    {
        return $this->get('documents/' . $id);
    }

    /**
     * @param $id
     * @return array
     */
    public function downloadDocument($id)
    {
        //TODO think about how binary data will be handled
        return $this->get('documents/' . $id . '/payload');
    }

    public function createCallback($source, $url)
    {
        return $this->post('callback', [
            'source' => $source,
            'url' => $url,
        ]);
    }

    public function combineDocument($name, array $source = [], $callback)
    {
        return $this->post('combine', [
            'name' => $name,
            'source' => $source,
            'callback' => $callback,
        ]);
    }

    public function convertToPdf($source, $callback)
    {
        return $this->post('pdf', [
            'source' => $source,
            'callback' => $callback,
        ]);
    }

    public function getHttpAdapter()
    {
        return $this->http;
    }

    /**
     * @param $method
     * @param $resource
     * @param array $request
     * @param array $headers
     * @return array|mixed|string
     */
    protected function performRequest($method, $resource, array $request, array $headers)
    {
        if (!$this->auth->hasAccessToken()) {
            $this->auth->authorize($this->clientId, $this->clientSecret);
        }

        try {
            $request['access_token'] = $this->auth->getAccessToken();

            return $this->http->$method($resource, $request, $headers);

        } catch (TokenExpiredException $e) {

            if ($this->options['token_refresh'] && $this->tokenRefreshes < $this->options['max_token_refresh']) {
                $this->auth->authorize($this->clientId, $this->clientSecret);
                $this->tokenRefreshes++;

                return $this->$method($resource, $request, $headers);
            } else {
                $this->tokenRefreshes = 0;
                throw $e;
            }
        }
    }
}
