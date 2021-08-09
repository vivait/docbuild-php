<?php

namespace Vivait\DocBuild;

use InvalidArgumentException;
use Vivait\DocBuild\Exception\FileException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Http\Adapter;
use Vivait\DocBuild\Http\Response;
use Vivait\DocBuild\Exception\HttpException;

use function array_key_exists;
use function get_resource_type;
use function in_array;
use function is_resource;
use function is_string;
use function rtrim;
use function sprintf;
use function stream_copy_to_stream;

class DocBuild
{

    public const TOKEN_EXPIRED = 'The access token provided has expired.';
    public const TOKEN_INVALID = 'The access token provided is invalid.';

    private Adapter $http;

    private string $apiKey;

    private string $url;

    /**
     * @param Adapter $client The HTTP client adapter to use for making requests.
     * @param string $apiKey API Key for the service.
     * @param string $url URL for the hosted service.
     */
    public function __construct(
        Adapter $client,
        string $apiKey,
        string $url
    ) {
        $this->http = $client;
        $this->apiKey = $apiKey;
        $this->url = $url;
    }

    /**
     * @param string $name
     * @param string $extension
     * @param null|resource $stream
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     *
     */
    public function createDocument(string $name, string $extension, $stream = null): ?array
    {
        $request = [
            'document[name]' => $name,
            'document[extension]' => $extension,
        ];

        if ($stream) {
            $file = $this->handleFileResource($stream);
            $request['document[file]'] = $file;
        }

        return $this->post('documents', $request)->toJsonArray();
    }

    /**
     * @param string $id The document ID to upload the payload for.
     * @param resource $stream The payload stream to be uploaded.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     *
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
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function getDocuments(): ?array
    {
        return $this->get('documents')->toJsonArray();
    }

    /**
     * @param string $id
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function getDocument(string $id): ?array
    {
        return $this->get('documents/' . $id)->toJsonArray();
    }

    /**
     * @param string $id The ID of the document to download.
     * @param resource $stream The stream to copy the contents to.
     *
     * @return void
     * @throws HttpException
     */
    public function downloadDocument(string $id, $stream): void
    {
        $documentContents = $this->get('documents/' . $id . '/payload', [], [])->getStream();

        stream_copy_to_stream($documentContents, $stream);
    }

    /**
     * @param string $source The source document ID to create the callback for.
     * @param string $url The callback URL.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     *
     */
    public function createCallback(string $source, string $url): ?array
    {
        return $this->post(
            'callback',
            [
                'source' => $source,
                'url' => $url,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $name The name of the new document.
     * @param array $sources An array of document IDs that need combining.
     * @param null|string $callback The callback URL.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function combineDocument(string $name, array $sources, ?string $callback = null): ?array
    {
        return $this->post(
            'combine',
            [
                'name' => $name,
                'source' => $sources,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $source The ID of the document to convert to a PDF.
     * @param null|string $callback The callback URL.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function convertToPdf(string $source, ?string $callback = null): ?array
    {
        return $this->post(
            'pdf',
            [
                'source' => $source,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $source The ID of the document to mailmerge.
     * @param array $fields The fields to mailmerge into the document.
     * @param null|string $callback The callback URL.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function mailMergeDocument(string $source, array $fields, $callback = null): ?array
    {
        return $this->post(
            'mailmerge',
            [
                'source' => $source,
                'fields' => $fields,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $source The ID of the document to mailmerge.
     * @param array $fields The fields to mailmerge into the document.
     * @param null|string $callback The callback URL.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function v2MailMergeDocument(string $source, array $fields, $callback = null): ?array
    {
        return $this->post(
            'v2/mailmerge',
            [
                'source' => $source,
                'fields' => $fields,
                'callback' => $callback,
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
     * @param array $recipients
     * @param null $callback
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function signable(
        string $source,
        string $signableKey,
        string $envelopeTitle,
        string $documentTitle,
        array $recipients,
        $callback = null
    ): ?array {
        foreach ($recipients as $recipient) {
            if (!array_key_exists('name', $recipient)) {
                throw new InvalidArgumentException("Recipient is missing a name.");
            }

            if (!array_key_exists('email', $recipient)) {
                throw new InvalidArgumentException("Recipient is missing an email.");
            }

            if (!array_key_exists('templateId', $recipient)) {
                throw new InvalidArgumentException("Recipient is missing a templateId.");
            }
        }

        return $this->post(
            'signable',
            [
                'signableKey' => $signableKey,
                'recipients' => $recipients,
                'envelopeTitle' => $envelopeTitle,
                'documentTitle' => $documentTitle,
                'callback' => $callback,
                'source' => $source,
            ]
        )->toJsonArray();
    }

    /**
     * Send a reminder about a Document that's out for Signable signatures.
     *
     * @param string $source
     * @param string $signableKey
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function signableReminder(string $source, string $signableKey): ?array
    {
        return $this->post(
            'signable/remind',
            [
                'signableKey' => $signableKey,
                'source' => $source,
            ]
        )->toJsonArray();
    }

    /**
     * Cancel a Document that's out for Signable signatures.
     *
     * @param string $source
     * @param string $signableKey
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function signableCancel(string $source, string $signableKey): ?array
    {
        return $this->post(
            'signable/cancel',
            [
                'signableKey' => $signableKey,
                'source' => $source,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $source The ID of the document to convert to a XLSX.
     * @param null|string $callback The callback URL.
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function convertToXlsx(string $source, ?string $callback = null): ?array
    {
        return $this->post(
            'xlsx',
            [
                'source' => $source,
                'callback' => $callback,
            ]
        )->toJsonArray();
    }

    /**
     * @param string $method The request method to use.
     * @param string $resource The resource to access on the base URL.
     * @param array $request Any parameters for the request (body).
     * @param array $headers The request's headers.
     *
     * @return Response
     * @throws HttpException
     */
    private function performRequest(
        string $method,
        string $resource,
        array $request,
        array $headers
    ): Response {
        $headers['X-AUTH-TOKEN'] = $this->apiKey;

        try {
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

            if (!in_array($e->getResponse()->getStatusCode(), [400, 401, 403])) {
                throw new UnauthorizedException($e->getMessage());
            }

            $body = $e->getResponse()->toJsonArray();

            if ($body === null || !array_key_exists('error_description', $body)) {
                throw $e;
            }

            $message = $body['error_description'];

            throw new UnauthorizedException(is_string($message) ? $message : null);
        }
    }

    /**
     * @param resource $stream
     *
     * @return resource
     */
    private function handleFileResource($stream)
    {
        if (!is_resource($stream) || get_resource_type($stream) != 'stream') {
            throw new FileException();
        } else {
            return $stream;
        }
    }

    /**
     * @param string $resource The resource to access on the base URL.
     * @param array $request Any parameters for the request (body).
     * @param array $headers The request's headers.
     *
     * @return Response
     * @throws HttpException
     */
    private function get(string $resource, array $request = [], array $headers = []): Response
    {
        return $this->performRequest('get', $resource, $request, $headers);
    }

    /**
     * @param string $resource The resource to access on the base URL.
     * @param array $request Any parameters for the request (body).
     * @param array $headers The request's headers.
     *
     * @return Response
     * @throws HttpException
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
        return sprintf("%s/%s", rtrim($this->url, '/'), $resource);
    }
}

