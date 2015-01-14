<?php

namespace spec\Vivait\DocBuild\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
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
        $this->setUrl('http://doc.build/api/');
    }


    function it_can_set_the_api_end_point()
    {
        $url = 'http://doc.build/api/';
        $this->setUrl($url)->shouldReturn($this);
    }

    function it_can_set_the_api_key()
    {
        $key = 'myapikey';
        $this->setKey($key)->shouldReturn($this);
    }

    function it_can_get_the_response_headers(ClientInterface $client)
    {
        $url = 'http://doc.build/api/documents/someid/payload';

        $response = new Response(200);
        $response->setHeader('Content-Disposition', 'attachment');
        $response->setHeader('filename', 'TestDocument1.docx');
        $response->setBody(Stream::factory(""));

        $client->get($url, Argument::any())->willReturn($response);

        $this->get('documents/someid/payload');

        $expected = [
            'Content-Disposition' => ['attachment'],
            'filename' => ['TestDocument1.docx']
        ];

        $this->getResponseHeaders()->shouldEqual($expected);
    }

    function it_can_get_the_last_response_code(ClientInterface $client)
    {
        //First request
        $url = 'http://doc.build/api/doesnotexist';
        $code = 404;

        $response = new Response($code);
        $response->setBody(Stream::factory(""));

        $client->get($url, Argument::any())->willReturn($response);
        $this->get('doesnotexist');

        //Second request
        $url = 'http://doc.build/api/exists';
        $code = 200;

        $response = new Response($code);
        $response->setBody(Stream::factory(""));

        $client->get($url, Argument::any())->willReturn($response);
        $this->get('exists');

        //Result
        $this->getResponseCode()->shouldBe(200);
        $this->getResponseCode()->shouldNotBe(404);
    }

    function it_can_perform_a_get_request(ClientInterface $client)
    {
        $url = 'http://doc.build/api/documents';

        $response = new Response(200);
        $response->setBody(
            Stream::factory(
                '[{"status":0, "id":"a1ec0371-966d-11e4-baee-08002730eb8a", "name":"Test Document 1", "extension":"docx" }]'
            )
        );

        $expected = [
            [
                'status' => 0,
                'id' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
                'name' => 'Test Document 1',
                'extension' => 'docx'
            ]
        ];

        $client->get($url, Argument::any())->willReturn($response);
        $this->get('documents')->shouldEqual($expected);
    }

    function it_can_perform_a_get_request_with_headers(ClientInterface $client)
    {

    }

    function it_can_perform_a_post_request()
    {

    }
}
