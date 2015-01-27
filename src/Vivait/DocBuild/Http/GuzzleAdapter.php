<?php

namespace Vivait\DocBuild\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
use Vivait\DocBuild\Exception\AdapterException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\TokenInvalidException;
use Vivait\DocBuild\Exception\UnauthorizedException;

class GuzzleAdapter implements HttpAdapter
{
    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var string
     */
    private $url;

    /**
     * @var Response
     */
    private $response;

    /**
     * @param ClientInterface $guzzle
     */
    public function __construct(ClientInterface $guzzle = null)
    {
        if (!$guzzle) {
            $this->guzzle = new Client();
        } else {
            $this->guzzle = $guzzle;
        }
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param $method
     * @param $resource
     * @param $options
     */
    private function sendRequest($method, $resource, $options)
    {
        try {
            $this->response = $this->guzzle->$method($this->url . $resource, $options);
        } catch (TransferException $e) {
            throw new AdapterException($e);
        }

        if ($this->response->getStatusCode() == 401) {
            $body = $this->response->json();

            if (array_key_exists('error_description', $body)) {
                $message = $body['error_description'];

                switch ($message) {
                    case self::TOKEN_EXPIRED :
                        throw new TokenExpiredException();
                        break;
                    case self::TOKEN_INVALID :
                        throw new TokenInvalidException();
                        break;
                    default:
                        throw new UnauthorizedException($message);
                }
            }
        }
    }

    public function get($resource, $request = [], $headers = [], $json = true)
    {
        $this->response = null;

        $options = [
            'exceptions' => false, //Disable http exceptions
            'query' => $request,
            'headers' => $headers,
        ];

        $this->sendRequest('get', $resource, $options);

        if($json){
            return json_decode($this->getResponseContent(), true);
        }

        return $this->getResponseContent();
    }

    public function post($resource, $request = [], $headers = [], $json = true)
    {
        $this->response = null;

        $options = [
            'exceptions' => false, //Disable http exceptions
            'body' => $request,
            'headers' => $headers,
        ];

        $this->sendRequest('post', $resource, $options);

        if($json){
            return json_decode($this->getResponseContent(), true);
        }

        return $this->getResponseContent();
    }

    public function getResponseCode()
    {
        return $this->response->getStatusCode();
    }

    public function getResponseHeaders()
    {
        return $this->response->getHeaders();
    }

    public function getResponseContent()
    {
        //TODO getBody() probably needs checking for null, as some responses may not return a body 204, whether guzzle handles
        //this by not setting a body, I'm not sure.

        return (string) $this->response->getBody();
    }


}
