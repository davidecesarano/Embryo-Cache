<?php 

    /**
     * ExpiresCacheTrait
     * 
     * @author Davide Cesarano <davide.cesarano@unipegaso.it>
     * @link   https://github.com/davidecesarano/embryo-cache 
     */

    namespace Embryo\Cache\Traits;

    use Embryo\Cache\Exceptions\InvalidArgumentException;

    trait ExpiresCacheTrait
    {
        /**
         * Set and return the expiration time value.
         *
         * @param int|null|\DateInterval $ttl
         * @return int
         * @throws InvalidArgumentException
         */
        protected function setExpiresAt($ttl = null): int
        {
            if (is_int($ttl)) {
                $expires_at = time() + $ttl;
            } elseif ($ttl instanceof \DateInterval) {
                $datetime = \DateTime::createFromFormat("U", date('Y-m-d H:i:s'));
                $expires_at = ($datetime) ? $datetime->add($ttl)->getTimestamp() : time();
            } elseif (!$ttl) {
                $expires_at = time() + intval($this->ttl);
            } else {
                throw new InvalidArgumentException("TTL must be an integer, an instance of DateInterval or a NULL");
            }
            return $expires_at;
        }
    }