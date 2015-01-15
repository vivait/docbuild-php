<?php

namespace Vivait\DocBuild;

use Vivait\DocBuild\Exception\BadRequestException;
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
     * @param $key
     * @param HttpAdapter $http
     */
    public function __construct($key, HttpAdapter $http = null)
    {
        if (!$http) {
            $this->http = new GuzzleAdapter();
        } else {
            $this->http = $http;
        }

        $this->http->setUrl(self::URL);
        $this->http->setKey($key);
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
        return $this->http->get('documents');
    }

    public function getDocument($id)
    {
        return $this->http->get('documents/' . $id);
    }

    public function downloadDocument($id)
    {
        return $this->http->get('documents/' . $id . '/payload', [], [], false);
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

    /**
     * @param $clientId
     * @param $clientSecret
     * @return string
     * @throws BadRequestException
     * @throws HttpException
     * @throws UnauthorizedException
     */
    public function authorise($clientId, $clientSecret)
    {
        $response = $this->http->get('oauth/token', ['client_id' => $clientId, 'client_secret' => $clientSecret]); //TODO

        $code = $this->http->getResponseCode();

        if ($code == 200 && array_key_exists('access_token', $response)) {
            return $response['access_token'];
        } elseif ($code == 400 || $code == 401 || $code == 403) {
            throw new UnauthorizedException(json_encode($response), $code);
        } else {
            throw new HttpException(json_encode($response), $code);
        }
    }

    public function authorize($clientId, $clientSecret)
    {
        return $this->authorise($clientId, $clientSecret);
    }
}
