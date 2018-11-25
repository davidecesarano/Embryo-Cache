<?php 

    /**
     * ExpiresCacheTrait
     * 
     * @author Davide Cesarano <davide.cesarano@unipegaso.it>
     * @link   https://github.com/davidecesarano/embryo-cache 
     */

    namespace Embryo\Cache\Traits;

    use DateInterval;
    use DateTitme;

    trait ExpiresCacheTrait
    {
        /**
         * Set and return the expiration time value.
         *
         * @param int|DateInterval $ttl
         * @return int
         */
        protected function setExpiresAt($ttl): int
        {
            if (is_int($ttl)) {
                $expires_at = time() + $ttl;
            } elseif ($ttl instanceof DateInterval) {
                $expires_at = DateTime::createFromFormat("U", time())->add($ttl)->getTimestamp();
            } elseif ($ttl === null) {
                $expires_at = time() + $this->ttl;
            } else {
                throw new \InvalidArgumentException("TTL must be an integer or an instance of DateInterval");
            }
            return $expires_at;
        }
    }