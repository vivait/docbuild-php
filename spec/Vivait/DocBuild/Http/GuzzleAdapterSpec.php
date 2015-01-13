<?php

namespace spec\Vivait\DocBuild\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\StreamInterface;
use GuzzleHttp\Subscriber\Mock;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class GuzzleAdapterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\Http\GuzzleAdapter');
        $this->shouldHaveType('Vivait\DocBuild\Http\HttpAdapter');
    }

    function let(ClientInterface $client)
    {
        $this->beConstructedWith($client);
    }


    function it_can_get_the_last_response_code(ClientInterface $client)
    {
        //First request
        $method = 'get'; $url = 'http://doc.build/api/doesnotexists'; $code = 404;

        $request = new Request($method, $url);
        $client->createRequest($method, $url, Argument::any())->willReturn($request);

        $response = new Response($code);
        $client->send($request)->willReturn($response);

        $this->sendRequest($method, $url);

        //Second request
        $method = 'get'; $url = 'http://doc.build/api/exists'; $code = 200;

        $request = new Request($method, $url);
        $client->createRequest($method, $url, Argument::any())->willReturn($request);

        $response = new Response($code);
        $client->send($request)->willReturn($response);

        $this->sendRequest($method, $url);

        //Result
        $this->getResponseCode()->shouldReturn(200);
        $this->getResponseCode()->shouldNotReturn(404);
    }

    function it_can_get_the_response_headers(ClientInterface $client)
    {
        $url = 'http://doc.build/api/';
        $request = new Request('get', $url);
        $client->createRequest('get', $url, Argument::any())->willReturn($request);

        $response = new Response(200);
        $response->setHeader('Content-Disposition', 'attachment');
        $response->setHeader('filename', 'TestDocument1.docx');

        $client->send($request)->willReturn($response);
        $this->sendRequest('get', $url);

        $expected = [
            'Content-Disposition' => ['attachment'],
            'filename' => ['TestDocument1.docx']
        ];

        $this->getResponseHeaders()->shouldEqual($expected);
    }

    function it_can_set_the_api_end_point()
    {
        $url = 'http://doc.build/api';
        $this->setUrl($url)->shouldReturn($this);
    }

    function it_can_set_the_api_key()
    {
        $key = 'myapikey';
        $this->setKey($key)->shouldReturn($this);
    }
}
