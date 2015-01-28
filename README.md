# Doc.Build

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

### Optional: Manually refresh access_token
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
	$docBuild->getAuth()->authorize();
	$docs = $docBuild->getDocuments();
}
```
