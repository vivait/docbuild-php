# Doc.Build [![Build Status](https://travis-ci.org/vivait/docbuild-php.svg)](https://travis-ci.org/vivait/docbuild-php)

## Installation

```
composer require vivait/docbuild-php
```

## Usage

See Doc.Build's Api documentation for detailed information on its methods.

Creating an instance of of `DocBuild` will use the `GuzzleAdapter` by default.
You can create your own adapter by implementing `HttpAdapter`.

The class requires your client id and client secret.

```php
$docBuild = new DocBuild($clientId, $clientSecret);

$docBuild->createDocument('ADocument', 'docx', '/path/to/file.docx');

$docs = $docBuild->getDocuments();

$docBuild->convertToPdf('documentid', 'http://mycallback.url/api');

```

### Http Client
The guzzle library is used to interact the API. However, you can use your own
adapter implementing `Vivait\DocBuild\Http\HttpAdapter` and injecting it into
the constructor:

```php
$docBuild = new DocBuild($clientId, $clientSecret, $options, new CustomHttpAdapter());
```

### Caching
This library uses the `doctrine/cache` library to cache `access_token` between
requestes. By default it will use the `Doctrine\Common\Cache\FilesystemCache`,
but this can be changed by injecting a cache that implements
`Doctrine\Common\Cache\Cache` into the constructor:

```php
$docBuild = new DocBuild($clientId, $clientSecret, $options, null, new ArrayCache());
```

### Manually refresh access_token
By default, the client will automatically refresh your `access_token`. However,
this behaviour can be changed by setting the following option, or passing this
options array into the constructor on instantiation.

```php
$docBuild->setOptions([
	'token_refresh' => false, //Default: true
]);

try {
	$docs = $docBuild->getDocuments();
} catch (TokenExpiredException $e) {
	//Have another go
}
```
