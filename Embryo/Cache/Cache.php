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

    use Embryo\Cache\Traits\{ExpiresCacheTrait, FileCacheTrait};
    use Embryo\Http\Factory\StreamFactory;
    use Psr\Http\Message\StreamFactoryInterface;
    use Psr\SimpleCache\CacheInterface;

    class Cache implements CacheInterface
    {
        use ExpiresCacheTrait;
        use FileCacheTrait;

        /**
         * @var string $cachePath
         */
        protected $cachePath;
        
        /**
         * @var string $cachePath
         */
        protected $streamFactory;

        /**
         * @var int|DateInterval $ttl
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
            $this->cachePath     = $cachePath;
            $this->streamFactory = ($streamFactory) ? $streamFactory : new StreamFactory; 
        }

        /**
         * Set default TTL.
         *
         * @param int|DateInterval $ttl
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
            $file = $this->getFile($key);
            if (!$file) {
                return $default;
            }

            $content = @unserialize($file->getContent());
            if (!$content) {
                $file->unlink();
                return $default;
            }

            $expires_at = $content[0];
            $output     = $content[1];

            if (time() >= $expires_at) {
                $file->unlink();
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
         * @param null|int|DateInterval $ttl
         * @return bool
         * @throws InvalidArgumentException
         */
        public function set($key, $value, $ttl = null)
        {
            $expires_at = $this->setExpiresAt($ttl);
            $content    = serialize([$expires_at, $value]);
            $file       = $this->setFile($key);

            if (!$file->setContent($content)) {
                return false;
            }
            return true;
        }

        /**
         * Delete an item from the cache by its unique key.
         *
         * @param string $key
         * @return bool
         * @throws InvalidArgumentException
         */
        public function delete($key)
        {
            $file = $this->getFile($key);
            $file->unlink();
        }

        /**
         * Wipes clean the entire cache's keys.
         *
         * @return bool
         */
        public function clear()
        {
            $success = true;
            if ($dir = opendir($this->cachePath)) {
                while (($file = readdir($dir)) !== false) {
                    if (!unlink($file)) {
                        $success = false;
                    }
                }
                closedir($dir);
            }
            return $success;
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
                throw new \InvalidArgumentException("keys must be either of type array or Traversable");
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
         * @param null|int|DateInterval $ttl
         * @return bool
         * @throws InvalidArgumentException
         */
        public function setMultiple($values, $ttl = null)
        {  
            if (!is_array($values)) {
                throw new \InvalidArgumentException("Values must be either of type array or Traversable");
            }

            $success = true;
            foreach ($values as $key => $value) {
                $success = $this->set($key, $value, $ttl) && $success;
            }
            return $success;            
        }

        /**
         * Deletes multiple cache items in a single operation.
         *
         * @param array $keys
         * @return bool
         * @throws InvalidArgumentException
         */
        public function deleteMultiple($keys)
        {
            if (!is_array($keys)) {
                throw new \InvalidArgumentException("keys must be either of type array or Traversable");
            }

            foreach ($keys as $key) {
                $this->delete($key);
            }
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
            return $this->get($key, $this) !== $this;
        }
    }