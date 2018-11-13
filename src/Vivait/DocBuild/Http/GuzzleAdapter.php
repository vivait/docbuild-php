<?php

namespace Vivait\DocBuild\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Response;
use Vivait\DocBuild\Exception\AdapterException;
use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\TokenInvalidException;
use Vivait\DocBuild\Exception\UnauthorizedException;

class GuzzleAdapter implements HttpAdapter
{
    /**
     * @var ClientInterface
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
     * @param int $returnType
     * @return array|string
     */
    private function sendRequest($method, $resource, $options, $returnType = self::RETURN_TYPE_JSON)
    {
        $this->response = null;

        $options['exceptions'] = true;

        try {
            $this->response = $this->guzzle->$method($this->url . $resource, $options);
        } catch (RequestException $e){
            if (($e->getCode() == 400 || $e->getCode() == 401 || $e->getCode() == 403) && $e->hasResponse()) {
                $body = $e->getResponse()->json();

                if (array_key_exists('error_description', $body)) {
                    $message = $body['error_description'];

                    switch ($message) {
                        case self::TOKEN_EXPIRED : throw new TokenExpiredException();
                        case self::TOKEN_INVALID : throw new TokenInvalidException();
                        default: throw new UnauthorizedException($message);
                    }
                }
            } else{
                throw new HttpException($e->getMessage(), $e->getCode());
            }
        } catch (TransferException $e) {
            throw new AdapterException($e->getMessage());
        }

        switch($returnType) {
            case self::RETURN_TYPE_STRING:
                return $this->getResponseContent();

            case self::RETURN_TYPE_JSON:
                return json_decode($this->getResponseContent(), true);

            case self::RETURN_TYPE_STREAM:
                return $this->response->getBody()->detach();
        }
    }

    public function get($resource, $request = [], $headers = [], $returnType = self::RETURN_TYPE_JSON)
    {
        $options = [
            'query' => $request,
            'headers' => $headers,
        ];

        return $this->sendRequest('get', $resource, $options, $returnType);
    }

    public function post($resource, $request = [], $headers = [], $returnType = self::RETURN_TYPE_JSON)
    {
        $options = [
            'body' => $request,
            'headers' => $headers,
        ];

        return $this->sendRequest('post', $resource, $options, $returnType);
    }

    public function getResponseCode()
    {
        return (int) $this->response->getStatusCode();
    }

    public function getResponseHeaders()
    {
        return $this->response->getHeaders();
    }

    public function getResponseContent()
    {
        return (string) $this->response->getBody();
    }


}
