# Embryo Cache
A minimal PSR-16 cache implementation with file stream system.

## Requirements
* PHP >= 7.1
* A [PSR-7](https://www.php-fig.org/psr/psr-7/) http message implementation and [PSR-17](https://www.php-fig.org/psr/psr-17/) http factory implementation (ex. [Embryo-Http](https://github.com/davidecesarano/embryo-http))
* A PSR response emitter (ex. [Embryo-Emitter](https://github.com/davidecesarano/Embryo-Emitter))

## Installation
Using Composer:
```
$ composer require davidecesarano/embryo-cache
```

## Usage
Set `Response` and `Emitter` objects. Later, set the `Cache` object passing it the cache directory path.
```php
use Embryo\Cache\Cache;
use Embryo\Http\Emitter\Emitter;
use Embryo\Http\Factory\ResponseFactory;

$emitter   = new Emitter;
$response  = (new ResponseFactory)->createResponse(200);
$cachePath = __DIR__.DIRECTORY_SEPARATOR.'cache';
$cache     = new Cache($cachePath);

if (!$cache->has('test')) {
    $cache->set('test', 'Hello World!', 3600);
}

$body = $response->getBody();
$body->write($cache->get('test', 'Default value!'));
$response = $response->withBody($body);

$emitter->emit($response);
```
In this example we check if the item cache `test` exists; if it not exists,  we set `test` item cache with value `Hello World!` and the Time To Live (TTL) in `3600` seconds. Later, we get the `test` item cache with default value if item cache not exists.

### Example

You may quickly test this using the built-in PHP server going to http://localhost:8000.

```
$ cd example
$ php -S localhost:8000
```

## Options
### `setDefaultTtl($ttl)`
You can set the default expiration TTL time. `$ttl` must be an integer or a `DateInterval` object.

## Collection

### Retrieving data
You may retrieve an item from the cache and you may also pass a default value as the second argument to the `get` method:
```php
$cache->get('key', 'default');
```

### Storing data
The `set` method may be used to set a new value onto a cache with an optional expiration TTL time:
```php
$cache->set('key', 'value', 3600);
```

### Deleting data
The `delete` method will remove a piece of data from the cache. If you would like the remove all data from the cache, you may use the `clear` method:
```php
$cache->delete('key');
$cache->clear();
```

### Retrieving multiple data
You may retrieve multiple items from the cache and you may also pass a default value as the second argument to the `getMultiple` method:
```php
$cache->getMultiple(['key1', 'key2'], 'default');
```

### Storing multiple data
The `setMultiple` method may be used to set a multiple values onto a cache with an optional expiration TTL time:
```php
$cache->setMultiple([
    'key1' => 'value',
    'key2' => 'value'
], 3600);
```

### Deleting multiple data
The `deleteMultiple` method will remove items from the cache:
```php
$cache->deleteMultiple(['key1', 'key2']);
```

### Determining if an item exists in the cache
To determine if an item is present in the cache, you may use the `has` method. The has method returns `true` if the item is present and is not `false`:
```php
if ($cache->has('key')) {
    //...
}
```