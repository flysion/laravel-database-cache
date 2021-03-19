<?php

namespace Flysion\Database;

class Cache
{
    /**
     * @var string|\Illuminate\Contracts\Cache\Repository
     */
    public $driver;

    /**
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    public $ttl;

    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @var string
     */
    public $key;

    /**
     * @var boolean
     */
    public $nullable = false;

    /**
     * @var Cache
     */
    public $prev;

    /**
     * @param string|\Illuminate\Contracts\Cache\Repository $driver
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param string|null $key
     * @param bool $nullable 如果缓存有读取到，且值为 null，则不再继续读数据库
     */
    public function __construct($driver, $ttl = null, $key = null, $nullable = false)
    {
        $this->driver = $driver;
        $this->ttl = $ttl;
        $this->key = $key;
        $this->nullable = $nullable;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     * @throws \Exception
     */
    public function driver()
    {
        if($this->driver instanceof \Illuminate\Contracts\Cache\Repository)
        {
            return $this->driver;
        }

        return cache()->driver($this->driver);
    }

    /**
     * @param null|string $lowKey
     * @return string
     */
    public function fullKey($lowKey = null)
    {
        return $this->prefix . (empty($this->key) ? $lowKey : $this->key);
    }

    /**
     * @param string|null $lowKey
     * @return array(boolean, mixed, Cache)
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($lowKey = null)
    {
        if($this->prev) {
            list($nullable, $value, $cache) = $this->prev->get($lowKey);
            if(!is_null($value) || $nullable) {
                return [$nullable, $value, $cache];
            }
        }

        return [$this->nullable, $this->driver()->get($this->fullKey($lowKey)), $this];
    }

    /**
     * @param mixed $value
     * @param string|null $lowKey
     * @throws \Exception
     */
    public function put($value, $lowKey = null)
    {
        if($this->prev) {
            $this->prev->put($value, $lowKey);
        }

        $this->driver()->put($this->fullKey($lowKey), $value, $this->ttl);
    }

    /**
     * @param \Closure $callback
     * @return array
     * @throws
     */
    public function remember(\Closure $callback, $lowKey = null)
    {
        list($nullable, $value, $cache) = $this->get($lowKey);
        if(!is_null($value)) {
            if($cache->prev) $cache->prev->put($value, $lowKey);
            return $value;
        } elseif($nullable) {
            return null;
        }

        $value = call_user_func($callback);
        if(!is_null($value)) {
            $this->put($value, $lowKey);
        }

        return $value;
    }
}