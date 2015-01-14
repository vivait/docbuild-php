<?php

namespace Vivait\DocBuild\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;

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
     * @var string
     */
    private $key;

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
     * @param $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function get($resource, $request = [], $headers = [], $json = true)
    {
        $this->response = null;

        $options = [
            'exceptions' => false,
            'query' => $request,
            'headers' => $headers,
        ];

        $this->response = $this->guzzle->get($this->url . $resource, $options);

        if($json){
            return json_decode($this->getResponseContent(), true);
        }

        return $this->getResponseContent();
    }

    public function post($resource, $request = [], $headers = [], $json = true)
    {
        $this->response = null;

        $options = [
            'exceptions' => false,
            'body' => $request,
            'headers' => $headers,
        ];

        $this->response = $this->guzzle->post($this->url . $resource, $options);

        if($json){
            return json_decode($this->getResponseContent(), true);
        }

        return $this->getResponseContent();
    }

//    public function sendRequest($method, $url, $request = [], $headers = [])
//    {
//        $this->response = null; //Resets previous response
//
//        $options = [
//            'exceptions' => false,
//            ''
//        ];
//
//        try {
//            $request = $this->guzzle->createRequest($method, $url, $options);
//            $this->response = $this->guzzle->send($request);
//        } catch (TooManyRedirectsException $e) {
//
//        } catch (RequestException $e) {
//            // dns/connection timeout
//        } catch (TransferException $e) {
//        }
//    }

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
        return $this->response->getBody()->getContents();
    }


}
