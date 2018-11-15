# Doc.Build [![Build Status](https://travis-ci.org/vivait/docbuild-php.svg)](https://travis-ci.org/vivait/docbuild-php)

## Installation

First, install the package itself

```
composer require vivait/docbuild-php
```

then install a [PSR-18](https://www.php-fig.org/psr/psr-18/) compliant HTTP client to use for requests such as:

```
composer require php-http/guzzle6-adapter
```

and finally install a message factory library that is compatible with [`php-http/message-factory`](https://packagist.org/packages/php-http/message-factory), e.g.

```
composer require php-http/message

# also require guzzlehttp/psr7 which is used by php-http/message
composer require guzzlehttp/psr7
``` 

## Usage

See Doc.Build's Api documentation for detailed information on its methods.

The class requires your client id and client secret.

```php
// Instantiate a HTTP client, in this example we use Guzzle 6
$client = GuzzleAdapter::createWithConfig([]);

$docBuild = new DocBuild($clientId, $clientSecret, $client);

$docBuild->createDocument('ADocument', 'docx', '/path/to/file.docx');

$docs = $docBuild->getDocuments();

$docBuild->convertToPdf('documentid', 'http://mycallback.url/api');

```

### Caching
This library uses the `doctrine/cache` library to cache `access_token` between
requests. By default it will use the `Doctrine\Common\Cache\FilesystemCache`,
but this can be changed by injecting a cache that implements
`Doctrine\Common\Cache\Cache` into the constructor:

```php
$docBuild = new DocBuild(
    $clientId, 
    $clientSecret, 
    GuzzleAdapter::createWithConfig([]), 
    $options, 
    null, 
    new ArrayCache()
);
```

### Manually refresh access_token
By default, the client will automatically refresh your `access_token`. However,
this behaviour can be changed by setting the following option, or passing this
options array into the constructor on instantiation.

```php
$docBuild->setOptions(
    [
        'token_refresh' => false, // Default: true
    ]
);

try {
	$docs = $docBuild->getDocuments();
} catch (TokenExpiredException $e) {
	// Have another go
}
```
