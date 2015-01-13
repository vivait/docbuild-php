<?php

namespace spec\Vivait\DocBuild\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class GuzzleAdapterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\Http\GuzzleAdapter');
        $this->shouldHaveType('Vivait\DocBuild\Http\HttpAdapter');
    }

    function let(Client $guzzle)
    {
        $this->beConstructedWith($guzzle);
    }

    function it_can_get_the_last_response_code(Client $guzzle)
    {
        $this->sendRequest('post', 'http://doc.build/api/notavalidroute');
        $request = new Request('post', 'http://doc.build/api/notavalidroute');
        $response = new Response(404);

        $guzzle->createRequest('post', 'http://doc.build/api/notavalidroute')->willReturn($request);
        $guzzle->send($request)->willReturn($response);

        $response->getStatusCode()->willReturn(404);

        $this->getResponseCode()->shouldReturn(404);
        $this->getResponseCode()->shouldNotReturn(400);
    }

    function it_can_get_the_last_response_content()
    {

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
