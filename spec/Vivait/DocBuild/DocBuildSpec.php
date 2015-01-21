<?php

namespace spec\Vivait\DocBuild;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Exception\BadCredentialsException;
use Vivait\DocBuild\Exception\TokenEmptyException;
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
        $this->beConstructedWith(null, null, [], $httpAdapter);
    }

    function it_requires_client_credentials_to_be_entered()
    {
        $this->setClientId(null);
        $this->setClientSecret(null);
        $this->shouldThrow(new BadCredentialsException())->duringAuthorize(null, null);

        $this->setClientId('myid');
        $this->setClientSecret(null);
        $this->shouldThrow(new BadCredentialsException())->duringAuthorize(null, null);

        $this->setClientId(null);
        $this->setClientSecret(null);
        $this->shouldThrow(new BadCredentialsException())->duringAuthorize('clientid');

        $this->setClientId(null);
        $this->setClientSecret(null);
        $this->shouldNotThrow(new BadCredentialsException())->duringAuthorize('clientid', 'clientsecret');

        $this->setClientId('clientId');
        $this->setClientSecret('clientSecret');
        $this->shouldNotThrow(new BadCredentialsException())->duringAuthorize();
    }

    function it_can_authorize_the_client(HttpAdapter $httpAdapter)
    {
        $token = 'myapitoken1';
        $response = ['access_token' => $token, 'expires_in' => 3600, 'token_type' => 'bearer', 'scope' => ''];
        $httpAdapter->get('oauth/token', [
            'client_id' => 'myid',
            'client_secret' => 'somesecret',
            'grant_type' => 'client_credentials'
        ])->willReturn($response);

        $httpAdapter->getResponseCode()->willReturn(200);

        $this->authorize('myid', 'somesecret')->shouldEqual($token);
    }

    function it_errors_with_invalid_credentials(HttpAdapter $httpAdapter)
    {
        $response = ["error" => "invalid_client", "error_description" =>"The client credentials are invalid"];
        $httpAdapter->get('oauth/token', [
            'client_id' => 'myid',
            'client_secret' => 'anincorrectsecret',
            'grant_type' => 'client_credentials'
        ])->willReturn($response);

        $httpAdapter->getResponseCode()->willReturn(401);

        $this->shouldThrow(new UnauthorizedException(json_encode($response), 401))->duringAuthorize('myid', 'anincorrectsecret');
    }

    function it_can_re_authorize_the_client_on_token_expiry(HttpAdapter $httpAdapter)
    {
        $this->setClientId($clientId = 'clientid');
        $this->setClientSecret($clientSecret = 'clientsecret');
        $this->setOptions(['token_refresh' => true]);

        $this->setToken('expiredapitoken');
        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';

        $response = ["error" => "invalid_grant", "error_description" => "The access token provided has expired."];
        $httpAdapter->get('documents', ['access_token' => 'expiredapitoken'], []);

        $this->getDocuments();
        //Reauth

//        $httpAdapter->get('oauth/token', [
//            'client_id' => $clientId,
//            'client_secret' => $clientSecret,
//            'grant_type' => 'client_credentials'
//        ]);
//        $this->authorize($clientId, $clientSecret)->shouldBeCalled();
//
//        $this->getDocument($id);
    }

    function it_can_optionally_not_auto_retry_auth(HttpAdapter $httpAdapter)
    {
//        $this->setToken('expiredapitoken');
//        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';
//
//        $response = ["error" => "invalid_grant", "error_description" => "The access token provided has expired."];
//        $httpAdapter->get('documents/' . $id, ['access_token' => 'expiredapitoken'], [])->willReturn($response);
//        $this->getDocument($id);
    }

//    function it_throws_access_denied_if_auth_token_invalid(HttpAdapter $httpAdapter)
//    {
//        $clientId = 'myclientid'; $clientSecret = 'myclientsecret';
//
//        $token = 'myauthtoken';
//
//        $expected = ['error' => 'invalid_client', 'error_description' => 'The client credentials are invalid']; //TODO
//
//        $httpAdapter->get('oauth/token', ['client_id' => $clientId, 'client_secret' => $clientSecret])->willReturn($expected);
//        $httpAdapter->getResponseCode()->willReturn(400);
//
//        $this->shouldThrow(new UnauthorizedException('{"error":"invalid_client","error_description":"The client credentials are invalid"}', 400))->during('authorize', [$clientId, $clientSecret]);
//    }

    function it_checks_a_token_has_been_set()
    {
        $this->shouldThrow(new TokenEmptyException())->during('checkToken');
    }

    function it_throws_an_exception_if_token_not_set()
    {
        $this->shouldThrow(new TokenEmptyException())->during('downloadDocument', [Argument::any()]);
    }


//    function it_checks_for_invalid_token(){}

    function it_can_get_a_list_of_documents(HttpAdapter $httpAdapter)
    {
        $this->setToken('myapitoken');

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

        $httpAdapter->get('documents', ['access_token' => 'myapitoken'], [])->willReturn($expected);
        $this->getDocuments()->shouldReturn($expected);
    }

    function it_can_download_a_document(HttpAdapter $httpAdapter)
    {
        $this->setToken('myapitoken');
        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';

        $httpAdapter->get('documents/' . $id . '/payload' , ['access_token' => 'myapitoken'], [])->shouldBeCalled();

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
        $this->setToken('myapitoken');
        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';

        $expected = [
            'status' => 0,
            'id' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
            'name' => 'Test Document 2',
            'extension' => 'docx',
        ];

        $httpAdapter->get('documents/' . $id, ['access_token' => 'myapitoken'], [])->willReturn($expected);
        $this->getDocument($id)->shouldReturn($expected);
    }

//    function it_can_create_a_document_with_a_payload(HttpAdapter $httpAdapter){}
//
//    function it_can_create_a_document_without_a_payload(HttpAdapter $httpAdapter){}
//
//    function it_can_upload_a_payload_to_an_existing_document(HttpAdapter $httpAdapter){}
//
//    function it_can_create_a_callback(HttpAdapter $httpAdapter){}
//
//    function it_can_combine_a_document(HttpAdapter $httpAdapter){}
//
//    function it_can_convert_a_doc_to_pdf(HttpAdapter $httpAdapter){}
}
