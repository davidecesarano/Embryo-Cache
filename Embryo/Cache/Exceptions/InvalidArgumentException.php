<?php 

    /**
     * InvalidArgumentException
     * 
     * @author Davide Cesarano <davide.cesarano@unipegaso.it>
     * @link   https://github.com/davidecesarano/embryo-cache 
     * @see    https://github.com/php-fig/simple-cache/blob/master/src/CacheInterface.php
     */

    namespace Embryo\Cache\Exceptions;

    class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException {}