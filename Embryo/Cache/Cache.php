<?php 

    /**
     * Cache
     * 
     * PSR-16 Simple Cache implementantion.
     * 
     * @author Davide Cesarano <davide.cesarano@unipegaso.it>
     * @link   https://github.com/davidecesarano/embryo-cache 
     * @see    https://github.com/php-fig/simple-cache/blob/master/src/CacheInterface.php
     */
    
    namespace Embryo\Cache;

    use Embryo\Cache\Exceptions\InvalidArgumentException;
    use Embryo\Cache\Traits\ExpiresCacheTrait;
    use Embryo\Http\Factory\StreamFactory;
    use Psr\Http\Message\StreamFactoryInterface;
    use Psr\SimpleCache\CacheInterface;

    class Cache implements CacheInterface
    {
        use ExpiresCacheTrait;

        /**
         * @var string $cachePath
         */
        protected $cachePath;
        
        /**
         * @var StreamFactoryInterface $streamFactory
         */
        protected $streamFactory;

        /**
         * @var int|\DateInterval $ttl
         */
        private $ttl;

        /**
         * Set cache path and StreamFactory.
         *
         * @param string $cachePath
         * @param StreamFactoryInterface $streamFactory
         */
        public function __construct(string $cachePath, StreamFactoryInterface $streamFactory = null)
        {
            $this->cachePath     = rtrim($cachePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
            $this->streamFactory = ($streamFactory) ? $streamFactory : new StreamFactory; 
        }

        /**
         * Set StreamFactory.
         * 
         * @param StreamFactoryInterface $streamFactory
         * @return self
         */
        public function setStreamFactory(StreamFactoryInterface $streamFactory): self 
        {
            $this->streamFactory = $streamFactory;
            return $this;
        }

        /**
         * Set default TTL.
         *
         * @param int|\DateInterval $ttl
         * @return self
         */
        public function setDefaultTtl($ttl): self
        {
            $this->ttl = $ttl;
            return $this;
        }

        /**
         * Fetches a value from the cache.
         *
         * @param string $key
         * @param mixed $default
         * @return mixed
         * @throws InvalidArgumentException
         */
        public function get($key, $default = null)
        {   
            if (!is_string($key)) {
                throw new InvalidArgumentException("key must be a string");   
            }
            
            $file = $this->cachePath.hash('sha256', $key);
            if (!file_exists($file)) {
                return $default;
            }

            $stream = $this->streamFactory->createStreamFromFile($file, 'r');
            $content = @unserialize($stream->getContents());
            if (!$content || !is_array($content) || count($content) != 2) {
                @unlink($stream->getMetadata('uri'));
                return $default;
            }

            $expires_at = $content[0];
            $output     = $content[1];

            if (time() >= $expires_at) {
                @unlink($stream->getMetadata('uri'));
                return $default;
            }
            return $output;
        }
        
        /**
         * Persists data in the cache, uniquely referenced 
         * by a key with an optional expiration TTL time.
         *
         * @param string $key
         * @param mixed $value
         * @param null|int|\DateInterval $ttl
         * @return bool
         * @throws InvalidArgumentException
         */
        public function set($key, $value, $ttl = null)
        {
            if (!is_string($key)) {
                throw new InvalidArgumentException("key must be a string");   
            }

            $expires_at = $this->setExpiresAt($ttl);
            $content    = serialize([$expires_at, $value]);
            $file       = $this->cachePath.hash('sha256', $key);

            try {
                $stream = $this->streamFactory->createStreamFromFile($file, 'r');
                $stream->write($content);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        }

        /**
         * Delete an item from the cache by its unique key.
         *
         * @param string $key
         * @return bool
         * @throws InvalidArgumentException
         */
        public function delete($key): bool
        {
            if (!is_string($key)) {
                throw new InvalidArgumentException("key must be a string");   
            }

            $file = $this->cachePath.hash('sha256', $key);
            if (!file_exists($file)) {
                return false;
            }

            $stream = $this->streamFactory->createStreamFromFile($file, 'r');
            return @unlink($stream->getMetadata('uri'));
        }

        /**
         * Wipes clean the entire cache's keys.
         *
         * @return bool
         */
        public function clear()
        {
            if (!$dir = opendir($this->cachePath)) {
                return false;
            }

            while (($file = readdir($dir)) !== false) {
                if ($file != '.' && $file != '..' && $file != '.gitignore') {
                    if (!@unlink($this->cachePath.$file)) {
                        return false;
                    }
                }
            }
            closedir($dir);
            return true;
        }

        /**
         * Obtains multiple cache items by their unique keys.
         *
         * @param array $keys
         * @param mixed $default
         * @return array
         * @throws InvalidArgumentException
         */
        public function getMultiple($keys, $default = null)
        {
            if (!is_array($keys)) {
                throw new InvalidArgumentException("keys must be either of type array or Traversable");
            }

            $values = [];
            foreach ($keys as $key) {
                $values[$key] = $this->get($key) ?: $default;
            }
            return $values;
        }

        /**
         * Obtains multiple cache items by their unique keys.
         *
         * @param array $values
         * @param null|int|\DateInterval $ttl
         * @return bool
         * @throws InvalidArgumentException
         */
        public function setMultiple($values, $ttl = null)
        {  
            if (!is_array($values)) {
                throw new InvalidArgumentException("Values must be either of type array or Traversable");
            }

            foreach ($values as $key => $value) {
                $set = $this->set($key, $value, $ttl);
                if (!$set) {
                    return false;
                }
            }
            return true;            
        }

        /**
         * Deletes multiple cache items in a single operation.
         *
         * @param array $keys
         * @return bool
         * @throws InvalidArgumentException
         */
        public function deleteMultiple($keys): bool
        {
            if (!is_array($keys)) {
                throw new InvalidArgumentException("keys must be either of type array or Traversable");
            }

            foreach ($keys as $key) {
                $delete = $this->delete($key);
                if (!$delete) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Determines whether an item is present in the cache.
         *
         * @param string $key
         * @return bool
         * @throws InvalidArgumentException
         */
        public function has($key)
        {
            if (!is_string($key)) {
                throw new InvalidArgumentException("key must be a string");   
            }
            return $this->get($key, $this) !== $this;
        }
    }