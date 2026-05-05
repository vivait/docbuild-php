<?php

namespace Vivait\DocBuild;

use InvalidArgumentException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Vivait\DocBuild\Exception\FileException;
use Vivait\DocBuild\Exception\UnauthorizedException;
use Vivait\DocBuild\Exception\HttpException;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Mime\Part\DataPart;

use function array_key_exists;
use function get_resource_type;
use function in_array;
use function is_resource;
use function is_string;
use function rtrim;
use function sprintf;
use function array_merge;
use function fwrite;
use function stream_get_meta_data;
use function basename;

class DocBuild
{
    public const TOKEN_EXPIRED = 'The access token provided has expired.';
    public const TOKEN_INVALID = 'The access token provided is invalid.';

    /**
     * @param HttpClientInterface $client The HTTP client to use for making requests.
     * @param string $apiKey API Key for the service.
     * @param string $url URL for the hosted service.
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $apiKey,
        private readonly string $url
    ) {
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

        return $this->safeToArray($this->post('documents', $request));
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

        return $this->safeToArray(
            $this->post(
                'documents/' . $id . '/payload',
                [
                    'document[file]' => $file,
                ]
            )
        );
    }

    /**
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function getDocuments(): ?array
    {
        return $this->safeToArray($this->get('documents'));
    }

    /**
     * @param string $id
     *
     * @return array|null The decoded JSON of the response.
     * @throws HttpException
     */
    public function getDocument(string $id): ?array
    {
        return $this->safeToArray($this->get('documents/' . $id));
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
        $response = $this->get('documents/' . $id . '/payload', [], []);

        foreach ($this->client->stream($response) as $chunk) {
            fwrite($stream, $chunk->getContent());
        }
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
        return $this->safeToArray(
            $this->post(
                'callback',
                [
                    'source' => $source,
                    'url' => $url,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'combine',
                [
                    'name' => $name,
                    'source' => $sources,
                    'callback' => $callback,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'pdf',
                [
                    'source' => $source,
                    'callback' => $callback,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'mailmerge',
                [
                    'source' => $source,
                    'fields' => $fields,
                    'callback' => $callback,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'v2/mailmerge',
                [
                    'source' => $source,
                    'fields' => $fields,
                    'callback' => $callback,
                ]
            )
        );
    }

    /**
     * Request signatures on a Document via Signable.
     *
     * @param string $source
     * @param string $signableKey
     * @param string $envelopeTitle
     * @param string $documentTitle
     * @param array<array> $recipients
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

        return $this->safeToArray(
            $this->post(
                'signable',
                [
                    'signableKey' => $signableKey,
                    'recipients' => $recipients,
                    'envelopeTitle' => $envelopeTitle,
                    'documentTitle' => $documentTitle,
                    'callback' => $callback,
                    'source' => $source,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'signable/remind',
                [
                    'signableKey' => $signableKey,
                    'source' => $source,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'signable/cancel',
                [
                    'signableKey' => $signableKey,
                    'source' => $source,
                ]
            )
        );
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
        return $this->safeToArray(
            $this->post(
                'xlsx',
                [
                    'source' => $source,
                    'callback' => $callback,
                ]
            )
        );
    }

    /**
     * @param string $method The request method to use.
     * @param string $resource The resource to access on the base URL.
     * @param array $request Any parameters for the request (body).
     * @param array $headers The request's headers.
     *
     * @return ResponseInterface
     * @throws HttpException
     */
    private function performRequest(
        string $method,
        string $resource,
        array $request,
        array $headers
    ): ResponseInterface {
        $method = strtoupper($method);
        $headers['X-AUTH-TOKEN'] = $this->apiKey;
        $options = ['headers' => $headers];

        if ($method === 'POST') {
            $containsFiles = false;
            foreach ($request as $value) {
                if (is_resource($value)) {
                    $containsFiles = true;
                    break;
                }
            }

            if ($containsFiles) {
                $fields = [];
                foreach ($request as $key => $value) {
                    if (is_resource($value)) {
                        $filename = null;
                        $meta = stream_get_meta_data($value);
                        if (isset($meta['uri'])) {
                            $filename = basename($meta['uri']);
                        }

                        $fields[$key] = new DataPart($value, $filename);
                    } else {
                        $fields[$key] = $value;
                    }
                }

                $formData = new FormDataPart($fields);
                $options['headers'] = array_merge($options['headers'], $formData->getPreparedHeaders()->toArray());
                $options['body'] = $formData->bodyToIterable();
            } else {
                $options['body'] = $request;
            }
        } else {
            $options['query'] = $request;
        }

        try {
            $response = $this->client->request(
                $method,
                $this->constructUrl($resource),
                $options
            );

            if ($response->getStatusCode() >= 400) {
                throw new HttpException($response->getStatusCode(), "HTTP Error " . $response->getStatusCode(), $response);
            }

            return $response;
        } catch (HttpException $e) {
            if ($e->getResponse() === null) {
                throw $e;
            }

            if (!in_array($e->getResponse()->getStatusCode(), [400, 401, 403])) {
                throw new UnauthorizedException($e->getMessage());
            }

            $body = $this->safeToArray($e->getResponse());

            if (!array_key_exists('error_description', $body)) {
                throw $e;
            }

            $message = $body['error_description'];

            throw new UnauthorizedException(is_string($message) ? $message : null);
        } catch (\Throwable $e) {
            throw new HttpException($e->getCode(), $e->getMessage());
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
     * @return ResponseInterface
     * @throws HttpException
     */
    private function get(string $resource, array $request = [], array $headers = []): ResponseInterface
    {
        return $this->performRequest('get', $resource, $request, $headers);
    }

    /**
     * @param string $resource The resource to access on the base URL.
     * @param array $request Any parameters for the request (body).
     * @param array $headers The request's headers.
     *
     * @return ResponseInterface
     * @throws HttpException
     */
    private function post(string $resource, array $request = [], array $headers = []): ResponseInterface
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

    private function safeToArray(ResponseInterface $response): ?array
    {
        if ($response->getStatusCode() === 204) {
            return null;
        }

        $content = $response->getContent(false);

        if ($content === '') {
            return null;
        }

        return json_decode($content, true);
    }
}

