<?php

namespace Flysion\Database;

/**
 *
 */
class Cache
{
    /**
     * @var string|\Illuminate\Contracts\Cache\Repository
     */
    protected $driver;

    /**
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    protected $ttl;

    /**
     * @var \DateTimeInterface|\DateInterval|int|null
     */
    protected $refreshTtl;

    /**
     * @var boolean
     */
    protected $allowEmpty;

    /**
     * @var null|string
     */
    protected $prefix;

    /**
     * @var static
     */
    protected $prev = null;

    /**
     * @var static
     */
    protected $next = null;

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool|null $allowEmpty
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @param string $prefix
     */
    public function __construct($refreshTtl = null, $ttl = null, $allowEmpty = null, $driver = null, $prefix = null)
    {
        $this->refreshTtl = $refreshTtl;
        $this->ttl = $ttl;
        $this->allowEmpty = $allowEmpty ?? false;
        $this->driver = $driver;
        $this->prefix = $prefix;
    }

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool|null $allowEmpty
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return static
     */
    public function next($refreshTtl = null, $ttl = null, $allowEmpty = null, $driver = null)
    {
        $instance = new static(
            $refreshTtl ?? $this->refreshTtl,
            $ttl ?? $this->ttl,
            $allowEmpty ?? $this->allowEmpty,
            $driver,
            $this->prefix
        );

        $this->next = $instance;
        $instance->prev = $this;
        return $instance;
    }

    /**
     * @return \Illuminate\Contracts\Cache\Repository
     * @throws \Exception
     */
    protected function driver()
    {
        if($this->driver instanceof \Illuminate\Contracts\Cache\Repository)
        {
            return $this->driver;
        }

        return cache()->driver($this->driver);
    }

    /**
     * @param string $key
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($key)
    {
        $result = $this->driver()->get($this->prefix . $key);

        if(is_null($result)) {
            return $this->prev ? $this->prev->get($key) : [$this, null, false, null];
        }

        if($result[1] !== "\0") {
            return [$this, $result[0], true, $result[1]];
        }

        if($this->allowEmpty) {
            return [$this, $result[0], true, null];
        }

        return $this->prev ? $this->prev->get($key) : [$this, null, false, null];
    }

    /**
     * @param string $key
     * @param mixed $data
     * @throws \Exception
     */
    public function put($key, $data)
    {
        $this->driver()->put($this->prefix . $key, [time(), $data ?? "\0"], $this->ttl);
        $this->putNext($key, $data);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @throws \Exception
     */
    public function putNext($key, $data)
    {
        if($this->next) {
            $this->next->put($key, $data);
        }
    }

    /**
     * @param \Closure $callback
     * @param string $key
     * @return mixed
     * @throws
     */
    public function remember(\Closure $callback, $key)
    {
        list($cache, $ts, $exists, $data) = $this->get($key);

        do {
            if(!$exists) {
                break;
            }

            // 数据还可以用，但是需要刷新

            if($this->refreshTtl && $ts + $this->refreshTtl < time()) {
                break;
            }

            $cache->putNext($key, $data);
            return $data;
        } while(false);

        try {
            $data = $callback();
        } catch (\Exception $e) {
            if($exists) {
                report($e);
                return $data; // 数据还可以用，刷新失败接着用
            }

            throw $e;
        }

        $cache->put($key, $data);

        return $data;
    }
}