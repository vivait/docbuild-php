# Doc.Build [![Build Status](https://travis-ci.org/vivait/docbuild-php.svg)](https://travis-ci.org/vivait/docbuild-php)

## Installation

```
composer require vivait/docbuild-php
```

and then write an Adapter that's compatible with the [Adapter interface](src/Vivait/DocBuild/Http/Adapter.php).

## Usage

See Doc.Build's Api documentation for detailed information on its methods.

The class requires your client id, client secret and a compatible [Adapter](src/Vivait/DocBuild/Http/Adapter.php).

```php
// Instantiate your adapter
$client = new MyAdapter();

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
