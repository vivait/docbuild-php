<?php

namespace spec\Vivait\DocBuild;

use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\HttpAdapter;

class DocBuildSpec extends ObjectBehavior
{
    /**
     * @var vfsStreamDirectory
     */
    private $tempDir;

    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\DocBuild\DocBuild');
    }

    function let(HttpAdapter $httpAdapter, Cache $cache)
    {
        $this->tempDir = vfsStream::setup('path');

        $httpAdapter->setUrl('http://api.doc.build/')->shouldBeCalled();

        $cache->contains('token')->willReturn(true);
        $cache->fetch('token')->willReturn('myapitoken');

        $this->beConstructedWith('myid', 'mysecret', [], $httpAdapter, $cache);
    }

    function it_authorizes_if_no_token_set(HttpAdapter $httpAdapter, Cache $cache)
    {
        $cache->contains('token')->willReturn(false);

        $response = ['access_token' => 'newtoken', 'expires_in' => 3600, 'token_type' => 'bearer', 'scope' => ''];
        $httpAdapter->get(
            'oauth/token',
            [
                'client_id' => 'myid',
                'client_secret' => 'mysecret',
                'grant_type' => 'client_credentials'
            ],
            [],
            HttpAdapter::RETURN_TYPE_JSON
        )->willReturn($response);

        $httpAdapter->getResponseCode()->willReturn(200);
        $cache->save('token', 'newtoken')->shouldBeCalled();

        $httpAdapter->get('documents', ['access_token' => 'newtoken'], [], HttpAdapter::RETURN_TYPE_JSON)->shouldBeCalled();

        $this->getDocuments();
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

        $httpAdapter->get('documents', ['access_token' => 'myapitoken'], [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);
        $this->getDocuments()->shouldReturn($expected);
    }

    function it_can_download_a_document(HttpAdapter $httpAdapter)
    {
        $expected = "Test Document";
        $id = 'a1ec0371-966d-11e4-baee-08002730eb8a';

        $file = vfsStream::newFile('file');
        $this->tempDir->addChild($file);

        $expectedFile = vfsStream::newFile('expected_file');
        $this->tempDir->addChild($expectedFile);

        $expectedStream = fopen('vfs://path/expected_file', 'w+');
        fwrite($expectedStream, $expected, strlen($expected));
        fseek($expectedStream, 0);

        $fileStream = fopen('vfs://path/file', 'w+');

        $httpAdapter->get('documents/' . $id . '/payload' , ['access_token' => 'myapitoken'], [], HttpAdapter::RETURN_TYPE_STREAM)->willReturn($expectedStream);

        $this->downloadDocument($id, $fileStream);

        if(($fileContents = $file->getContent()) != $expected) {
            throw new \Exception("File steam contents of '" . $fileContents ."' does not equal expected '" . $expected . "'");
        }
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

        $httpAdapter->get('documents/' . $id, ['access_token' => 'myapitoken'], [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);
        $this->getDocument($id)->shouldReturn($expected);
    }

    function it_can_create_a_document_with_a_payload(HttpAdapter $httpAdapter)
    {
        $file = vfsStream::newFile('file');
        $file->setContent('somecontent');
        $this->tempDir->addChild($file);

        $file = fopen('vfs://path/file', 'r');

        $expected = [
            "status" => 0,
            "id" => "a1ec0371-966d-11e4-baee-08002730eb8a",
            "name" => "Test Document 1",
            "extension" => "docx",
        ];

        $request = [
            'document[name]' => 'Test File 1',
            'document[extension]' => 'docx',
            'document[file]'=> $file,
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('documents', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->createDocument('Test File 1', 'docx', $file)->shouldReturn($expected);
    }

    function it_can_create_a_document_without_a_payload(HttpAdapter $httpAdapter)
    {
        $expected = [
            "status" => 0,
            "id" => "a1ec0371-966d-11e4-baee-08002730eb8a",
            "name" => "Test Document 1",
            "extension" => "docx",
        ];

        $request = [
            'document[name]' => 'Test File 1',
            'document[extension]' => 'docx',
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('documents', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->createDocument('Test File 1', 'docx', null)->shouldReturn($expected);
    }


    function it_can_upload_a_payload_to_an_existing_document(HttpAdapter $httpAdapter)
    {
        $file = vfsStream::newFile('file');
        $file->setContent('somecontent');
        $this->tempDir->addChild($file);

        $file = fopen('vfs://path/file', 'r');

        $expected = [];
        $request = [
            'document[file]' => $file,
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('documents/a1ec0371-966d-11e4-baee-08002730eb8a/payload', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->uploadDocument('a1ec0371-966d-11e4-baee-08002730eb8a', $file)->shouldReturn($expected);
    }

    function it_can_create_a_callback(HttpAdapter $httpAdapter)
    {
        $expected = [];

        $request = [
            'source' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
            'url' => 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a',
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('callback', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->createCallback('a1ec0371-966d-11e4-baee-08002730eb8a', 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a', null)
            ->shouldReturn($expected);
    }

    function it_can_combine_a_document(HttpAdapter $httpAdapter)
    {
        $expected = [];

        $request = [
            'name' => 'Combined Document 2',
            'source' => [
                'a1ec0371-966d-11e4-baee-08002730eb8a',
                'a1ec0371-966d-11e4-baee-08002730eb8b',
            ],
            'callback' => 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a',
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('combine', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->combineDocument('Combined Document 2', ["a1ec0371-966d-11e4-baee-08002730eb8a", "a1ec0371-966d-11e4-baee-08002730eb8b"] , 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a')
            ->shouldReturn($expected);
    }


    function it_can_convert_a_doc_to_pdf(HttpAdapter $httpAdapter, Cache $cache)
    {
        $expected = [];

        $request = [
            'source' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
            'callback' => 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a',
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('pdf', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->convertToPdf('a1ec0371-966d-11e4-baee-08002730eb8a', 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a')
            ->shouldReturn($expected);
    }

    function it_can_mail_merge_a_document(HttpAdapter $httpAdapter, Cache $cache)
    {
        $expected = [];

        $request = [
            'source' => 'a1ec0371-966d-11e4-baee-08002730eb8a',
            'fields' => ['firstName' => 'Milly', 'lastName' => 'Merged'],
            'callback' => 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a',
            'access_token' => 'myapitoken',
        ];

        $httpAdapter->post('mailmerge', $request, [], HttpAdapter::RETURN_TYPE_JSON)->willReturn($expected);

        $this->mailMergeDocument('a1ec0371-966d-11e4-baee-08002730eb8a', ['firstName' => 'Milly', 'lastName' => 'Merged'], 'http://localhost/test/callback?id=a1ec0371-966d-11e4-baee-08002730eb8a')
            ->shouldReturn($expected);
    }

    function it_errors_with_invalid_credentials(HttpAdapter $httpAdapter, Cache $cache)
    {
        $this->setClientSecret('anincorrectsecret');
        $cache->contains('token')->willReturn(false);

        $httpAdapter->get(
            'oauth/token',
            [
                'client_id' => 'myid',
                'client_secret' => 'anincorrectsecret',
                'grant_type' => 'client_credentials'
            ],
            [],
            HttpAdapter::RETURN_TYPE_JSON
        )->willThrow(new UnauthorizedException());

        $httpAdapter->getResponseCode()->willReturn(401);

        $this->shouldThrow(new UnauthorizedException())->duringGetDocuments();
    }

    function it_can_authorize_the_client(HttpAdapter $httpAdapter)
    {
        $response = ['access_token' => 'myapitoken', 'expires_in' => 3600, 'token_type' => 'bearer', 'scope' => ''];
        $httpAdapter->get(
            'oauth/token',
            [
                'client_id' => 'myid',
                'client_secret' => 'mysecret',
                'grant_type' => 'client_credentials'
            ],
            [],
            HttpAdapter::RETURN_TYPE_JSON
        )->willReturn($response);

        $httpAdapter->getResponseCode()->willReturn(200);

        $httpAdapter->get('documents', ['access_token' => 'myapitoken',], [], HttpAdapter::RETURN_TYPE_JSON)->willReturn([]);

        $this->getDocuments();
    }

    function it_clears_the_cache_if_exception(HttpAdapter $httpAdapter, Cache $cache)
    {
        $this->setOptions(['token_refresh' => false]);

        $cache->contains('token')->willReturn(true);
        $cache->fetch('token')->willReturn('expiredtoken');

        $httpAdapter->get('documents', ['access_token' => 'expiredtoken'], [], HttpAdapter::RETURN_TYPE_JSON)
            ->willThrow(new TokenExpiredException("The access token provided has expired."));

        $cache->delete('token')->shouldBeCalled();

        $this->shouldThrow(new TokenExpiredException())->duringGetDocuments();
    }
}
