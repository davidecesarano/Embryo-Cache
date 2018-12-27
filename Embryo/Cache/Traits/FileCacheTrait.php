<?php 

    /**
     * FileCacheTrait
     * 
     * @author Davide Cesarano <davide.cesarano@unipegaso.it>
     * @link   https://github.com/davidecesarano/embryo-cache 
     */

    namespace Embryo\Cache\Traits;

    use Psr\Http\Message\StreamInterface;

    trait FileCacheTrait 
    {
        /**
         * @var StreamInterface $stream
         */
        private $stream;

        /**
         * Return an instance of StreamInterface if it 
         * creates stream from file, otherwise return 
         * false if file doesn't exists.
         *
         * @param string $key
         * @return bool|self
         */
        protected function getFile($key)
        {
            $file = $this->getFilePath($key);
            if (!file_exists($file)) {
                return false;
            }

            $this->stream = $this->streamFactory->createStreamFromFile($file, 'r');
            return $this;
        }

        /**
         * Return an instance of StreamInterface after create
         * stream from file.
         *
         * @param string $key
         * @return StreamInterface
         */
        protected function setFile($key): self
        {
            $file         = $this->getFilePath($key);
            $this->stream = $this->streamFactory->createStreamFromFile($file, 'w');
            return $this;
        }

        /**
         * Return file content.
         *
         * @return string
         */
        protected function getContent(): string
        {
            return $this->stream->getContents();
        }

        /**
         * Set file content.
         *
         * @param string $output
         * @return int
         */
        protected function setContent(string $output): int
        {
            return $this->stream->write($output);
        }

        /**
         * Delete file.
         *
         * @return bool
         */     
        protected function unlink(): bool
        {
            @unlink($this->stream->getMetadata('uri'));
            return true;
        }

        /**
         * Get file path.
         *
         * @param string $key
         * @return string
         */
        private function getFilePath(string $key): string
        {
            $hash = hash("sha256", $key);
            return $this->cachePath.'/'.$hash;
        }
    }