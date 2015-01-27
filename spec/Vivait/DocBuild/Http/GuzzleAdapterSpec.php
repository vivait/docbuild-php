<?php

namespace spec\Vivait\DocBuild\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Stream\Stream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Exception\AdapterException;

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
        $url = 'http://doc.build/api/documents';
        $response = new Response(200);
        $response->setBody(Stream::factory(""));

        $client->get(
            $url,
            [
                'exceptions' => false,
                'query' => [],
                'headers' => ['Auth' => 'myapikey'],
            ]
        )->shouldBeCalled()->willReturn($response);

        $this->get('documents', [], ['Auth' => 'myapikey']);
    }

    function it_can_perform_a_get_request_with_query(ClientInterface $client)
    {
        $url = 'http://doc.build/api/documents';
        $response = new Response(200);
        $response->setBody(Stream::factory(""));

        $client->get(
            $url,
            [
                'exceptions' => false,
                'query' => ['id' => '1234'],
                'headers' => ['Auth' => 'myapikey'],
            ]
        )->shouldBeCalled()->willReturn($response);

        $this->get('documents', ['id' => '1234'], ['Auth' => 'myapikey']);
    }

    function it_can_perform_a_post_request(ClientInterface $client)
    {
        $url = 'http://doc.build/api/documents';
        $response = new Response(200);
        $response->setBody(
            Stream::factory(
                '{"status":0, "id":"a1ec0371-966d-11e4-baee-08002730eb8a", "name":"Test Document 1", "extension":"docx" }'
            )
        );

        $client->post(
            $url,
            [
                'exceptions' => false,
                'body' => ['document[name]' => 'Test File 1', 'document[extension]' => 'docx'],
                'headers' => ['Auth' => 'myapikey'],
            ]
        )->shouldBeCalled()->willReturn($response);

        $this->post('documents', ['document[name]' => 'Test File 1', 'document[extension]' => 'docx'], ['Auth' => 'myapikey']);
    }

    function it_can_perform_a_post_request_with_parameters(ClientInterface $client)
    {
        $url = 'http://doc.build/api/documents';
        $response = new Response(200);
        $response->setBody(Stream::factory(""));

        $client->post(
            $url,
            [
                'exceptions' => false,
                'body' => ['document[name]' => 'Test File 1', 'document[extension]' => 'docx'],
                'headers' => ['Auth' => 'myapikey'],
            ]
        )->shouldBeCalled()->willReturn($response);

        $this->post(
            'documents',
            ['document[name]' => 'Test File 1', 'document[extension]' => 'docx'],
            ['Auth' => 'myapikey']
        );
    }

    function it_throws_exceptions_for_adapter_errors(ClientInterface $client)
    {
        $e = new TransferException();
        $client->get(Argument::any(), Argument::any())->willThrow($e);

        $adapterException = new AdapterException(null, $e);
        $this->shouldThrow($adapterException)->duringGet(Argument::any());
    }
}
