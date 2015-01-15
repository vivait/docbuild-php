<?php

namespace spec\Vivait\DocBuild;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuildSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\DocBuild');
    }

    function let(HttpAdapter $httpAdapter)
    {
        $httpAdapter->setUrl('http://doc.build/api')->shouldBeCalled();
        $this->beConstructedWith($httpAdapter);
    }

    function it_can_authorise_the_client(HttpAdapter $httpAdapter)
    {
        $clientId = 'myclientid'; $clientSecret = 'myclientsecret';

        $token = 'myauthtoken';

        $expected = ['access_token' => $token, 'expires_in' => 3600, 'token_type' => 'bearer', 'scope' => '']; //TODO

        $httpAdapter->get('oauth/token', ['client_id' => $clientId, 'client_secret' => $clientSecret])->willReturn($expected);
        $httpAdapter->getResponseCode()->willReturn(200);

        $this->shouldNotThrow('Vivait\DocBuild\Exception\UnauthorizedException')->during('authorise', [$clientId, $clientSecret]);
        $this->shouldNotThrow('Vivait\DocBuild\Exception\HttpException')->during('authorise', [$clientId, $clientSecret]);

        $this->authorise($clientId, $clientSecret)->shouldReturn($token);
    }

    function it_throws_access_denied_if_auth_token_invalid(HttpAdapter $httpAdapter)
    {
        $clientId = 'myclientid'; $clientSecret = 'myclientsecret';

        $token = 'myauthtoken';

        $expected = ['error' => 'invalid_client', 'error_description' => 'The client credentials are invalid']; //TODO

        $httpAdapter->get('oauth/token', ['client_id' => $clientId, 'client_secret' => $clientSecret])->willReturn($expected);
        $httpAdapter->getResponseCode()->willReturn(400);

        $this->shouldThrow(new UnauthorizedException('{"error":"invalid_client","error_description":"The client credentials are invalid"}', 400))->during('authorise', [$clientId, $clientSecret]);
    }

    function it_can_get_a_list_of_documents(HttpAdapter $httpAdapter)
    {
        $expected = [
            [
                'status' => 0,
                'id' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
                'name' => 'Test Document 1',
                'extension' => 'docx',
            ],
            [
                'status' => 0,
                'id' => 'ee572a33-43c9-45c2-939a-009d0d48241f',
                'name' => 'Test Document 2',
                'extension' => 'docx',
            ],
        ];

        $httpAdapter->get('documents')->willReturn($expected);
        $this->getDocuments()->shouldReturn($expected);
    }

    function it_can_download_a_document(HttpAdapter $httpAdapter)
    {
        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';

        $httpAdapter->get('documents/' . $id . '/payload' , [], [], false)->shouldBeCalled();

        $headers = [
            'Content-Disposition' => ['attachment'],
            'filename' => ['TestDocument1.docx']
        ];

        $httpAdapter->getResponseHeaders()->willReturn($headers);

        $this->downloadDocument($id);
        $this->getHttpAdapter()->getResponseHeaders()->shouldReturn($headers);
    }

    function it_can_get_document_info(HttpAdapter $httpAdapter)
    {
        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';

        $expected = [
            'status' => 0,
            'id' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
            'name' => 'Test Document 2',
            'extension' => 'docx',
        ];

        $httpAdapter->get('documents/' . $id)->willReturn($expected);
        $this->getDocument($id)->shouldReturn($expected);
    }

    function it_can_create_a_document_with_a_payload(HttpAdapter $httpAdapter){}

    function it_can_create_a_document_without_a_payload(HttpAdapter $httpAdapter){}

    function it_can_upload_a_payload_to_an_existing_document(HttpAdapter $httpAdapter){}

    function it_can_create_a_callback(HttpAdapter $httpAdapter){}

    function it_can_combine_a_document(HttpAdapter $httpAdapter){}

    function it_can_convert_a_doc_to_pdf(HttpAdapter $httpAdapter){}
}
