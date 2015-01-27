<?php

namespace spec\Vivait\DocBuild;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Auth\Auth;
use Vivait\DocBuild\Exception\BadCredentialsException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuildSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\DocBuild');
    }

    function let(HttpAdapter $httpAdapter, Auth $auth)
    {
        $httpAdapter->setUrl('http://doc.build/api/')->shouldBeCalled();
        $this->beConstructedWith('myid', 'mysecret', [], $httpAdapter, $auth);
    }

    function it_can_get_a_list_of_documents(HttpAdapter $httpAdapter, Auth $auth)
    {
        $auth->hasAccessToken()->willReturn(true);
        $auth->getAccessToken()->willReturn('myapitoken');

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


    function it_can_download_a_document(HttpAdapter $httpAdapter, Auth $auth)
    {
        $auth->hasAccessToken()->willReturn(true);
        $auth->getAccessToken()->willReturn('myapitoken');

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

    function it_can_get_document_info(HttpAdapter $httpAdapter, Auth $auth)
    {

        $auth->hasAccessToken()->willReturn(true);
        $auth->getAccessToken()->willReturn('myapitoken');

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

    function it_authorizes_if_no_token_set(HttpAdapter $httpAdapter, Auth $auth)
    {
        $auth->hasAccessToken()->willReturn(false);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $auth->authorize('myid', 'mysecret')->shouldBeCalled();

        $auth->getAccessToken()->willReturn('newaccesstoken');
        $httpAdapter->get('documents', ['access_token' => 'newaccesstoken'], [])->shouldBeCalled();


        $this->getDocuments();
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
