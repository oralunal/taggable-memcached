<?php

namespace Oralunal\TaggableMemcached;

use Exception;
use Memcached;
use Oralunal\TaggableMemcached\Exceptions\GetException;
use Oralunal\TaggableMemcached\Exceptions\SetException;
use Oralunal\TaggableMemcached\Exceptions\SetTagException;

class Cache
{
    public static Cache $instance;
    protected Memcached $memcached;
    protected Memcached $clone;
    protected ?string $lastKey;
    protected array|string|null $tags;

    /**
     * Cache constructor.
     *
     * @param string $server
     * @param int $port
     * @param string $prefix
     */
    public function __construct(string $server = 'localhost', int $port = 11211, string $prefix = '')
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer($server, $port); // Default memcached port
        if($prefix !== '')
            $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix); // Prefix all keys with the app namespace and environment
    }

    /**
     * Get the instance of the Cache class
     *
     * @param string $server
     * @param int $port
     * @param string $namespace
     * @return Cache
     */
    public static function getInstance(string $server = 'localhost', int $port = 11211, string $prefix = ''): Cache
    {
        if (! isset(self::$instance)) {
            self::$instance = new Cache($server, $port, $prefix);
        }

        return self::$instance;
    }

    /**
     * Get a value from the cache by key
     *
     * @param string $key
     * @return mixed
     * @throws GetException
     */
    public function get(string $key): mixed
    {
        $return = $this->memcached->get($key);

        if($this->memcached->getLastErrorCode() == Memcached::RES_SUCCESS) {
            if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
                return null; // Key not found, returning false can be misleading because the value related to the key might be "false"
            } else {
                return $return;
            }
        } else {
            throw new GetException($this->memcached->getLastErrorMessage(), $this->memcached->getLastErrorCode());
        }
    }

    /**
     * Get a value from the cache by tag
     *
     * @param string $key
     * @param $value
     * @param int $ttl
     * @return Cache
     * @throws Exception
     */
    public function set(string $key, $value, int $ttl = 60 * 60 * 24): static
    {

        if ($this->memcached->set($key, $value, $ttl)) {
            $this->lastKey = $key;

            // Set the tags
            if(!is_null($this->tags)){

                $tags = $this->tags;

                if(is_array($tags)){
                    foreach($tags as $tag) {
                        $this->setTag($tag, $key);
                    }
                }else {
                    $this->setTag($tags, $key);
                }
            }

        }else {
            $this->memcached->delete($key); // Try to delete the key if it already exists
            throw new SetException($this->memcached->getLastErrorMessage(), $this->memcached->getLastErrorCode());
        }

        return $this;
    }

    /**
     * @param array|string $tags
     * @return $this
     */
    public function withTags(array|string $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @throws SetTagException
     */
    public function setTag(string $tag, string $key): void
    {
        if(!is_null($this->tags)) $this->tags = null;

        try {
            $keys = $this->get($tag);

            if(is_null($keys))
                $keys = [];

            if(!in_array($key, $keys)){
                $keys[] = $key;
                $this->set($tag, $keys);
            }else{
                // Do nothing
                // The key is already in the tag
            }
        } catch (Exception $e) {
            throw new SetTagException($e->getMessage(), $e->getCode());
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