<?php

namespace spec\Vivait\DocBuild;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuildSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\DocBuild');
    }

    function let($key = 'myapikey', HttpAdapter $httpAdapter)
    {
        $httpAdapter->setUrl('http://doc.build/api')->shouldBeCalled();
        $httpAdapter->setKey($key)->shouldBeCalled();
        $this->beConstructedWith($key, $httpAdapter);
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
            'filename' => ['"TestDocument1.docx"']
        ];

        $httpAdapter->getResponseHeaders()->willReturn($headers);

        $this->downloadDocument($id);
        $this->getResponseHeaders()->shouldReturn($headers);
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
}
