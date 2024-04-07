<?php

namespace Oralunal\TaggableMemcached;

use Exception;
use Memcached;

class Cache
{
    public static Cache $instance;
    protected Memcached $memcached;
    protected string $lastKey;
    protected mixed $lastValue;

    /**
     * Cache constructor.
     *
     * @param string $server
     * @param int $port
     * @param string $namespace
     */
    public function __construct(string $server = 'localhost', int $port = 11211, string $namespace = '')
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer($server, $port); // Default memcached port
        $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $namespace); // Prefix all keys with the app namespace and environment
    }

    /**
     * Get the instance of the Cache class
     *
     * @param string $server
     * @param int $port
     * @param string $namespace
     * @return Cache
     */
    public static function getInstance(string $server = 'localhost', int $port = 11211, string $namespace = ''): Cache
    {
        if (! isset(self::$instance)) {
            self::$instance = new Cache($server, $port, $namespace);
        }

        return self::$instance;
    }

    /**
     * Get a value from the cache by key
     *
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function get(string $key): mixed
    {
        $return = $this->memcached->get($key);

        if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND || $return === false)
            throw new Exception($this->memcached->getLastErrorMessage(), $this->memcached->getLastErrorCode());

        return $return;
    }

    /**
     * @throws Exception
     */
    public function set(string $key, $value, int $ttl = 60 * 60 * 24): static
    {
        if ($this->memcached->set($key, $value, $ttl)) {
            $this->lastKey = $key;
            $this->lastValue = $value;
            return $this;
        }

        throw new Exception($this->memcached->getLastErrorMessage(), $this->memcached->getLastErrorCode());
    }


    /**
     * Set a tag for a key
     *
     * @param array|string $tag
     * @return bool
     * @throws Exception
     */
    public function setTag(array|string $tag): bool
    {
        try {
            // First get all keys for the tag
            $keys = $this->memcached->get($tag);
            if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
                $keys = [];
            }

            if (is_array($tag)) {
                foreach ($tag as $t) {
                    $keys[] = $t;

                }
            } else {
                $keys[] = $tag;
            }

            return $this->memcached->set($tag, $keys);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        return $this->memcached->delete($key);
    }

    /**
     * @param string $tag
     * @return bool
     */
    public function deleteByTag(string $tag): bool
    {
        $keys = $this->memcached->get($tag);
        if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
            return true;
        }

        foreach ($keys as $key) {
            $this->memcached->delete($key);
        }

        return $this->memcached->delete($tag);
    }

    /**
     * @return bool
     */
    public function flush(): bool
    {
        return $this->memcached->flush();
    }
}