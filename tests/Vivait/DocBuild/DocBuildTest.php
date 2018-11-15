<?php

namespace Tests\Vivait\DocBuild;

use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Vivait\DocBuild\DocBuild;
use Vivait\DocBuild\Exception\CacheException;
use Vivait\DocBuild\Exception\FileException;
use Vivait\DocBuild\Exception\TokenExpiredException;
use Vivait\DocBuild\Exception\TokenInvalidException;
use Vivait\DocBuild\Exception\UnauthorizedException;

class DocBuildTest extends TestCase
{

    /**
     * @var MockObject
     */
    private $cache;

    /**
     * @var MockObject
     */
    private $client;

    /**
     * @var DocBuild
     */
    private $docBuild;

    /**
     * @var vfsStreamDirectory
     */
    private $filesystem;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->client = $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        
        $this->cache = $this->getMockBuilder(Cache::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->filesystem = vfsStream::setup('path');
        
        $this->docBuild = new DocBuild('id', 'secret', [], $this->client, $this->cache);
    }

    /**
     * @test
     */
    public function itWillAuthoriseIfNoTokenIsSet(): void
    {
        $this->cache->expects(self::once())
            ->method('contains')
            ->with('token')
            ->willReturnOnConsecutiveCalls(false)
        ;

        $this->cache->expects(self::once())
            ->method('save')
            ->with('token', 'newtoken')
        ;

        $responseData = [
            'access_token' => 'newtoken',
            'expires_in'   => 3600,
            'token_type'   => 'bearer',
            'scope'        => '',
        ];

        $this->client->expects(self::exactly(2))
            ->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], \json_encode($responseData)),
                new Response(200, [], \json_encode($responseData))
            )
        ;

        $this->docBuild->getDocuments();
    }

    /**
     * @test
     */
    public function itWillCorrectlyDownloadADocument(): void
    {
        $this->cache->expects(self::once())
            ->method('contains')
            ->willReturn(true)
        ;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn('accessToken')
        ;

        $actualFile = vfsStream::newFile('actualFile');
        $fakeExternalFile = vfsStream::newFile('expectedFile');
        $fakeExternalFile->setContent('Test Content');

        $this->filesystem->addChild($actualFile);
        $this->filesystem->addChild($fakeExternalFile);

        $fakeExternalStream = \fopen('vfs://path/expectedFile', 'r');

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], $fakeExternalStream))
        ;

        $actualFileStream = \fopen('vfs://path/actualFile', 'w+');


        $this->docBuild->downloadDocument('test', $actualFileStream);

        self::assertSame($fakeExternalFile->getContent(), $actualFile->getContent());
    }

    /**
     * @test
     * @dataProvider retryableStatusProvider
     *
     * @param int $status
     */
    public function itErrorsWithInvalidCredentials(int $status): void
    {
        $this->expectException(UnauthorizedException::class);

        $this->cache->expects(self::once())
            ->method('contains')
            ->with('token')
            ->willReturn(true)
        ;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('token')
            ->willReturn('badToken')
        ;

        $this->cache->expects(self::once())
            ->method('delete')
            ->with('token')
            ->willReturn(true)
        ;

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Create an anonymous class instead of mocking the exception since we can't mock `getCode` as it's final
        $exception = $this->createHttpException($status, $request, ['error_description' => 'unrecognised error']);

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->docBuild->getDocuments();
    }

    /**
     * @test
     * @dataProvider retryableStatusProvider
     *
     * @param int $status
     */
    public function itWillThrowAnExceptionIfTheCacheKeyCannotBeDeleted(int $status): void
    {
        $this->expectException(CacheException::class);

        $this->cache->expects(self::once())
            ->method('contains')
            ->with('token')
            ->willReturn(true)
        ;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('token')
            ->willReturn('badToken')
        ;

        $this->cache->expects(self::once())
            ->method('delete')
            ->with('token')
            ->willReturn(false)
        ;

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Create an anonymous class instead of mocking the exception since we can't mock `getCode` as it's final
        $exception = $this->createHttpException($status, $request, ['error_description' => 'unrecognised error']);

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->docBuild->getDocuments();
    }

    /**
     * @test
     * @dataProvider retryableStatusProvider
     *
     * @param int $status
     */
    public function itWillThrowAnExceptionIfTheTokenIsExpired(int $status): void
    {
        $this->expectException(TokenExpiredException::class);

        $this->cache->expects(self::once())
            ->method('contains')
            ->with('token')
            ->willReturn(true)
        ;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('token')
            ->willReturn('badToken')
        ;

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Create an anonymous class instead of mocking the exception since we can't mock `getCode` as it's final
        $exception = $this->createHttpException($status, $request, ['error_description' => DocBuild::TOKEN_EXPIRED]);

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->docBuild->getDocuments();
    }

    /**
     * @test
     * @dataProvider retryableStatusProvider
     *
     * @param int $status
     */
    public function itWillThrowAnExceptionIfTheTokenIsInvalid(int $status): void
    {
        $this->expectException(TokenInvalidException::class);

        $this->cache->expects(self::once())
            ->method('contains')
            ->with('token')
            ->willReturn(true)
        ;

        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('token')
            ->willReturn('badToken')
        ;

        $request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // Create an anonymous class instead of mocking the exception since we can't mock `getCode` as it's final
        $exception = $this->createHttpException($status, $request, ['error_description' => DocBuild::TOKEN_INVALID]);

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->willThrowException($exception)
        ;

        $this->docBuild->getDocuments();
    }

    /**
     * @return array
     */
    public function retryableStatusProvider(): array
    {
        return [[400], [401], [403]];
    }

    /**
     * @test
     */
    public function itWillThrowAnExceptionIfItDoesNotReceiveAStreamWhenCreatingADocument(): void
    {
        $this->expectException(FileException::class);
        $this->docBuild->createDocument('name', 'pdf', 'not a stream');
    }

    /**
     * @test
     */
    public function itWillThrowAnExceptionIfSignableRecipientHasNoName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipient is missing a name.');

        $this->docBuild->signable(
            '1',
            '2',
            '3',
            '4',
            [
                [
                    'name'       => 'a',
                    'email'      => 'b',
                    'templateId' => 'c',
                ],
                [
                    'email'      => 'e',
                    'templateId' => 'f',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function itWillThrowAnExceptionIfSignableRecipientHasNoEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipient is missing an email.');

        $this->docBuild->signable(
            '1',
            '2',
            '3',
            '4',
            [
                [
                    'name'       => 'a',
                    'email'      => 'b',
                    'templateId' => 'c',
                ],
                [
                    'name'       => 'd',
                    'templateId' => 'f',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function itWillThrowAnExceptionIfSignableRecipientHasNoTemplateId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipient is missing a templateId.');

        $this->docBuild->signable(
            '1',
            '2',
            '3',
            '4',
            [
                [
                    'name'       => 'a',
                    'email'      => 'b',
                    'templateId' => 'c',
                ],
                [
                    'name'  => 'd',
                    'email' => 'e',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function itWillThrowAnExceptionIfNoAccessTokenIsProvidedDuringAuthorisation(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->cache->expects(self::any())
            ->method('contains')
            ->with('token')
            ->willReturn(false)
        ;

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], \json_encode([])))
        ;

        $this->docBuild->getDocuments();
    }

    /**
     * @param int              $status
     * @param RequestInterface $request
     * @param array            $responseData
     *
     * @return HttpException
     */
    private function createHttpException(
        int $status,
        RequestInterface $request,
        array $responseData
    ): HttpException
    {
        return new class($status, $request, $responseData) extends HttpException {

            /**
             * @param int              $status
             * @param RequestInterface $request
             * @param array            $responseData
             */
            public function __construct(int $status, RequestInterface $request, array $responseData)
            {
                parent::__construct(
                    'Test',
                    $request,
                    new Response($status, [], \json_encode($responseData))
                );

                $this->code = $status;
            }
        };
    }
}
