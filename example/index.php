<?php 
    
    require __DIR__ . '/../vendor/autoload.php';
    
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