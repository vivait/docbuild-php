<?php

namespace Vivait\DocBuild;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vivait\DocBuild\Exception\FileException;
use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\TokenInvalidException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\GuzzleAdapter;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuild
{
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
     * @var Cache
     */
    private $cache;

    /**
     * @param null $clientId
     * @param null $clientSecret
     * @param array $options
     * @param HttpAdapter $http
     * @param Cache $cache
     */
    public function __construct($clientId, $clientSecret, array $options = [], HttpAdapter $http = null, Cache $cache = null)
    {
        $this->optionsResolver = new OptionsResolver();
        $this->setOptions($options);

        if ($http) {
            $this->http = $http;
        } else {
            $this->http = new GuzzleAdapter();
        }

        if ($cache) {
            $this->cache = $cache;
        } else {
            $this->cache = new FilesystemCache(sys_get_temp_dir());
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $this->http->setUrl($this->options['url']);
    }

    public function setOptions(array $options = [])
    {
        $this->configureOptions($this->optionsResolver);
        $this->options = $this->optionsResolver->resolve($options);
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'token_refresh' => true,
            'cache_key' => 'token',
            'url' => 'http://api.doc.build/',
        ]);
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

    /**
     * @param $method
     * @param $resource
     * @param array $request
     * @param array $headers
     * @return array|mixed|string
     * @throws TokenExpiredException
     */
    protected function performRequest($method, $resource, array $request, array $headers)
    {
        if ($this->cache->contains($this->options['cache_key'])) {
            $accessToken = $this->cache->fetch($this->options['cache_key']);
        } else {
            $accessToken = $this->authorize();
            $this->cache->save($this->options['cache_key'], $accessToken);
        }

        try {
            $request['access_token'] = $accessToken;

            return $this->http->$method($resource, $request, $headers);

        } catch (UnauthorizedException $e) {
            $this->cache->delete($this->options['cache_key']);

            if ($e instanceof TokenExpiredException || $e instanceof TokenInvalidException) {
                if ($this->options['token_refresh']) {
                    return $this->$method($resource, $request, $headers);
                }
            }

            throw $e;
        }
    }

    /**
     * @return string
     */
    public function authorize()
    {
        $response = $this->http->get(
            'oauth/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'client_credentials'
            ]
        );

        $code = $this->http->getResponseCode();

        if ($code == 200 && array_key_exists('access_token', $response)) {
            return $response['access_token'];
        } else {
            throw new HttpException("No access token was provided in the response", $code);
        }
    }


    /**
     * @param $name
     * @param $extension
     * @param null $stream
     * @return array|mixed|string
     */
    public function createDocument($name, $extension, $stream = null)
    {
        $request = [
            'document[name]' => $name,
            'document[extension]' => $extension
        ];

        if ($stream) {
            $file = $this->handleFileResource($stream);
            $request['document[file]'] = $file;
        }

        return $this->post('documents', $request);
    }

    /**
     * @param $id
     * @param $stream
     * @return array|mixed|string
     */
    public function uploadDocument($id, $stream)
    {
        $file = $this->handleFileResource($stream);

        return $this->post('documents/' . $id . '/payload', [
            'document[file]' => $file
        ]);
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
     * @param $stream
     * @return void
     */
    public function downloadDocument($id, $stream)
    {
        $documentContents = $this->get('documents/' . $id . '/payload');

        fwrite($stream, $documentContents, strlen($documentContents));
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
     * @param $stream
     * @return \SplFileObject
     */
    protected function handleFileResource($stream)
    {
        if (!is_resource($stream) && get_resource_type($stream) != 'stream') {
            throw new FileException();
        } else {
            return $stream;
        }
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
}
