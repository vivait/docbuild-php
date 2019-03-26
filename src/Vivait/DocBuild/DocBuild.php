<?php

namespace Vivait\DocBuild;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vivait\DocBuild\Exception\CacheException;
use Vivait\DocBuild\Exception\FileException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\Adapter;
use Vivait\DocBuild\Http\Response;
use Vivait\DocBuild\Exception\HttpException;
use Vivait\DocBuild\Model\Options;

class DocBuild
{

    public const TOKEN_EXPIRED = 'The access token provided has expired.';
    public const TOKEN_INVALID = 'The access token provided is invalid.';

    /**
     * @var Adapter
     */
    private $http;

    /**
     * @var string
     */
    private $oauthClientSecret;

    /**
     * @var string
     */
    private $oauthClientId;

    /**
     * @var Options
     */
    private $options;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param string|null $clientId     OAuth client ID.
     * @param string|null $clientSecret OAuth client secret.
     * @param array       $options      Options for the DocBuild client,
     * @param Adapter     $client       The HTTP client adapter to use for making requests.
     * @param Cache       $cache        An optional
     */
    public function __construct(
        $clientId,
        $clientSecret,
        array $options = [],
        Adapter $client,
        Cache $cache = null
    ) {
        $this->options = $this->transformOptions($options);

        $this->http = $client;

        if ($cache) {
            $this->cache = $cache;
        } else {
            $this->cache = new FilesystemCache(\sys_get_temp_dir());
        }

        $this->oauthClientId = $clientId;
        $this->oauthClientSecret = $clientSecret;
    }

    /**
     * @throws HttpException
     * 
     * @return string
     */
    public function authorize(): string
    {
        // We can't use $this->performRequest() because we could get caught in a loop
        $response = $this->http->sendRequest(
            'post',
            $this->constructUrl('oauth/token'),
            [
                'client_id'     => $this->oauthClientId,
                'client_secret' => $this->oauthClientSecret,
                'grant_type'    => 'client_credentials',
            ],
            []
        );

        $data = $response->toJsonArray();

        if ($data !== null && \array_key_exists('access_token', $data)) {
            return $data['access_token'];
        } else {
            throw new \RuntimeException("No access token was provided in the response");
        }
    }


    /**
     * @param string        $name
     * @param string        $extension
     * @param null|resource $stream
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function createDocument(string $name, string $extension, $stream = null): ?array
    {
        $request = [
            'document[name]'      => $name,
            'document[extension]' => $extension,
        ];

        if ($stream) {
            $file = $this->handleFileResource($stream);
            $request['document[file]'] = $file;
        }

        return $this->post('documents', $request)->toJsonArray();
    }

    /**
     * @param string   $id     The document ID to upload the payload for.
     * @param resource $stream The payload stream to be uploaded.
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function uploadDocument(string $id, $stream): ?array
    {
        $file = $this->handleFileResource($stream);

        return $this->post(
            'documents/' . $id . '/payload',
            [
                'document[file]' => $file,
            ]
        )->toJsonArray();
    }

    /**
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function getDocuments(): ?array
    {
        return $this->get('documents')->toJsonArray();
    }

    /**
     * @param string $id
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function getDocument(string $id): ?array
    {
        return $this->get('documents/' . $id)->toJsonArray();
    }

    /**
     * @param string   $id     The ID of the document to download.
     * @param resource $stream The stream to copy the contents to.
     *
     * @throws HttpException
     *
     * @return void
     */
    public function downloadDocument(string $id, $stream): void
    {
        $documentContents = $this->get('documents/' . $id . '/payload', [], [])->getStream();

        \stream_copy_to_stream($documentContents, $stream);
    }

    /**
     * @param string $source The source document ID to create the callback for.
     * @param string $url    The callback URL.
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function createCallback(string $source, string $url): ?array
    {
        return $this->post(
            'callback',
            [
                'source' => $source,
                'url'    => $url,
            ]
        )->toJsonArray();
    }

    /**
     * @param string      $name     The name of the new document.
     * @param array       $sources  An array of document IDs that need combining.
     * @param null|string $callback The callback URL.
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function combineDocument(string $name, array $sources, ?string $callback = null): ?array
    {
        return $this->post(
            'combine',
            [
                'name'     => $name,
                'source'   => $sources,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string      $source   The ID of the document to convert to a PDF.
     * @param null|string $callback The callback URL.
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function convertToPdf(string $source, ?string $callback = null): ?array
    {
        return $this->post(
            'pdf',
            [
                'source'   => $source,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string      $source   The ID of the document to mailmerge.
     * @param array       $fields   The fields to mailmerge into the document.
     * @param null|string $callback The callback URL.
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function mailMergeDocument(string $source, array $fields, $callback = null): ?array
    {
        return $this->post(
            'mailmerge',
            [
                'source'   => $source,
                'fields'   => $fields,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string      $source   The ID of the document to mailmerge.
     * @param array       $fields   The fields to mailmerge into the document.
     * @param null|string $callback The callback URL.
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function v2MailMergeDocument($source, Array $fields, $callback = null): ?array
    {
        return $this->post(
            'v2/mailmerge',
            [
                'source'   => $source,
                'fields'   => $fields,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @return Adapter
     */
    public function getHttpClient(): Adapter
    {
        return $this->http;
    }

    /**
     * @param string $oauthClientSecret
     */
    public function setOauthClientSecret(string $oauthClientSecret): void
    {
        $this->oauthClientSecret = $oauthClientSecret;
    }

    /**
     * @param string $oauthClientId
     */
    public function setOauthClientId(string $oauthClientId): void
    {
        $this->oauthClientId = $oauthClientId;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $this->transformOptions($options);
    }

    /**
     * @param string      $source
     * @param array       $emailAddresses
     * @param string      $apiUrl
     * @param string      $adobeClientId
     * @param string      $adobeClientSecret
     * @param string      $adobeRefreshToken
     * @param null|string $callback
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function adobeSign(
        $source,
        array $emailAddresses,
        $apiUrl,
        $adobeClientId,
        $adobeClientSecret,
        $adobeRefreshToken,
        $callback = null
    ): ?array
    {
        return $this->post(
            'adobe-sign',
            [
                'source'         => $source,
                'emailAddresses' => $emailAddresses,
                'apiUrl'         => $apiUrl,
                'callback'       => $callback,
                'clientId'       => $adobeClientId,
                'clientSecret'   => $adobeClientSecret,
                'token'          => $adobeRefreshToken,
            ]
        )->toJsonArray();
    }

    /**
     * Request signatures on a Document via Signable.
     *
     * @param string $source
     * @param string $signableKey
     * @param string $envelopeTitle
     * @param string $documentTitle
     * @param array  $recipients
     * @param null   $callback
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function signable(
        $source,
        $signableKey,
        $envelopeTitle,
        $documentTitle,
        array $recipients,
        $callback = null
    ): ?array
    {
        foreach ($recipients as $recipient) {
            if ( ! array_key_exists('name', $recipient)) {
                throw new \InvalidArgumentException("Recipient is missing a name.");
            }

            if ( ! array_key_exists('email', $recipient)) {
                throw new \InvalidArgumentException("Recipient is missing an email.");
            }

            if ( ! array_key_exists('templateId', $recipient)) {
                throw new \InvalidArgumentException("Recipient is missing a templateId.");
            }
        }

        return $this->post(
            'signable',
            [
                'signableKey'   => $signableKey,
                'recipients'    => $recipients,
                'envelopeTitle' => $envelopeTitle,
                'documentTitle' => $documentTitle,
                'callback'      => $callback,
                'source'        => $source,
            ]
        )->toJsonArray();
    }

    /**
     * Send a reminder about a Document that's out for Signable signatures.
     *
     * @param string $source
     * @param string $signableKey
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function signableReminder($source, $signableKey): ?array
    {
        return $this->post(
            'signable/remind',
            [
                'signableKey' => $signableKey,
                'source'      => $source,
            ]
        )->toJsonArray();
    }

    /**
     * Cancel a Document that's out for Signable signatures.
     *
     * @param string $source
     * @param string $signableKey
     *
     * @throws HttpException
     *
     * @return array|null The decoded JSON of the response.
     */
    public function signableCancel($source, $signableKey): ?array
    {
        return $this->post(
            'signable/cancel',
            [
                'signableKey' => $signableKey,
                'source'      => $source,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $method     The request method to use.
     * @param string $resource   The resource to access on the base URL.
     * @param array  $request    Any parameters for the request (body).
     * @param array  $headers    The request's headers.
     *
     * @throws HttpException
     *
     * @return Response
     */
    private function performRequest(
        string $method,
        string $resource,
        array $request,
        array $headers
    ): Response
    {
        if ($this->cache->contains($this->options->getCacheKey())) {
            $accessToken = $this->cache->fetch($this->options->getCacheKey());
        } else {
            $accessToken = $this->authorize();

            $this->cache->save($this->options->getCacheKey(), $accessToken);
        }

        try {
            $request['access_token'] = $accessToken;

            return $this->http->sendRequest(
                $method,
                $this->constructUrl($resource),
                $request,
                $headers
            );
        } catch (HttpException $e) {
            if ($e->getResponse() === null) {
                throw $e;
            }

            if ( ! \in_array($e->getResponse()->getStatusCode(), [400, 401, 403])) {
                throw $e;
            }

            $body = $e->getResponse()->toJsonArray();

            if ($body === null || ! \array_key_exists('error_description', $body)) {
                throw $e;
            }

            $message = $body['error_description'];

            if( ! $this->cache->delete($this->options->getCacheKey())){
                throw new CacheException('Could not delete the key in the cache. Do you have permission?');
            }

            switch ($message) {
                // We're unauthorised, so get the token again if we need to
                case self::TOKEN_EXPIRED:
                case self::TOKEN_INVALID:
                    if ($this->options->shouldTokenRefresh()) {
                        return $this->$method($resource, $request, $headers);
                    }

                    break;
                default:
                    // Can't re-authenticate, throw unauthorized
                    throw new UnauthorizedException(\is_string($message) ? $message : null);
            }
        }
    }

    /**
     * @param resource $stream
     *
     * @return resource
     */
    private function handleFileResource($stream)
    {
        if ( ! \is_resource($stream) || \get_resource_type($stream) != 'stream') {
            throw new FileException();
        } else {
            return $stream;
        }
    }

    /**
     * @param string $resource The resource to access on the base URL.
     * @param array  $request  Any parameters for the request (body).
     * @param array  $headers  The request's headers.
     *
     * @throws HttpException
     *
     * @return Response
     */
    private function get(string $resource, array $request = [], array $headers = []): Response
    {
        return $this->performRequest('get', $resource, $request, $headers);
    }

    /**
     * @param string $resource   The resource to access on the base URL.
     * @param array  $request    Any parameters for the request (body).
     * @param array  $headers    The request's headers.
     *
     * @throws HttpException
     *
     * @return Response
     */
    private function post(string $resource, array $request = [], array $headers = []): Response
    {
        return $this->performRequest('post', $resource, $request, $headers);
    }

    /**
     * @param string $resource The resource to access on the base URL.
     *
     * @return string The base URL combined with the resource.
     */
    private function constructUrl(string $resource): string
    {
        return \sprintf("%s/%s", \rtrim($this->options->getUrl(), '/'), $resource);
    }

    /**
     * @param array $options The raw user options.
     *
     * @return Options
     */
    private function transformOptions(array $options = []): Options
    {
        $resolver = new OptionsResolver;
        $resolver->setDefaults(
            [
                'token_refresh' => true,
                'cache_key'     => 'token',
                'url'           => 'https://api.docbuild.vivait.co.uk/',
            ]
        );

        return new Options($resolver->resolve($options));
    }
}
