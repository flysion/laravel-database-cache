<?php

namespace Flysion\Database;

/**
 *
 */
class Cache
{
    /**
     * @var object
     */
    protected $object;

    /**
     * @var string|\Illuminate\Contracts\Cache\Repository
     */
    protected $driver;

    /**
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    protected $ttl;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var boolean
     */
    protected $allowNull = false;

    /**
     * @var bool
     */
    private $isRefresh = false;

    /**
     * @param object $object
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     */
    public function __construct($object, $key, $ttl = null, $allowNull = false, $driver = null)
    {
        $this->object = $object;
        $this->key = $key;
        $this->ttl = $ttl;
        $this->allowNull = $allowNull;
        $this->driver = $driver;
    }

    /**
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return Cache
     */
    public function cache($key = null, $ttl = null, $allowNull = false, $driver = null)
    {
        return new static($this, $key ?? $this->key, $ttl ?? $this->ttl, $allowNull, $driver);
    }

    /**
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @return Cache
     */
    public function cacheFromArray($key = null, $ttl = null, $allowNull = false)
    {
        return $this->cache($key, $ttl, $allowNull, 'array');
    }

    /**
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @return Cache
     */
    public function cacheFromFile($key = null, $ttl = null, $allowNull = false)
    {
        return $this->cache($key, $ttl, $allowNull, 'file');
    }

    /**
     * @param null $key
     * @return mixed
     */
    protected function cacheKey($key = null)
    {
        return $this->object->cacheKey($key ?? $this->key);
    }

    /**
     * 销毁缓存
     * 可将多级缓存全部销毁
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @return bool
     */
    public function cacheDestroy()
    {
        if($this->object instanceof static) {
            $this->object->cacheDestroy();
        }

        return $this->cacheDriver()->delete($this->cacheKey());
    }

    /**
     * 绕过缓存直接从源头读取数据，并写入缓存
     * 可用于主动刷新缓存
     *
     * @return static
     */
    public function cacheRefresh()
    {
        if($this->object instanceof static) {
            $this->object->cacheRefresh();
        }

        $this->isRefresh = true;

        return $this;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     * @throws \Exception
     */
    protected function cacheDriver()
    {
        if($this->driver instanceof \Illuminate\Contracts\Cache\Repository)
        {
            return $this->driver;
        }

        return cache()->driver($this->driver);
    }

    /**
     * @param string $name
     * @param mixed[] $arguments
     * @return mixed
     * @throws
     */
    public function __call($name, $arguments)
    {
        if(!$this->isRefresh)
        {
            $result = $this->cacheDriver()->get($this->cacheKey());
            if (!is_null($result)) {
                return $result;
            }

            if ($result === "\0" && $this->allowNull) {
                return null;
            }
        }

        $result = $this->object->{$name}(...$arguments);

        $this->cacheDriver()->put($this->cacheKey(), $result ?? "\0", $this->ttl);

        return $result;
    }
}