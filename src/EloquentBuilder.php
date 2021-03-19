<?php

namespace Flysion\Database;

class EloquentBuilder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param string|null $key
     * @param bool $nullable false:如果缓存有读取到，且值为 null，则不再继续读数据库
     * @return static
     */
    public function queryCache($driver = null, $ttl = null, $key = null, $nullable = false)
    {
        if(!$driver) {
            $driver = method_exists($this->getModel(), 'cacheDriver') ? $this->getModel()->cacheDriver() : (
                $this->getModel()->cacheDriver ?? config('cache.default')
            );
        }

        $cache = new Cache($driver, $ttl, $key, $nullable);
        $cache->prefix = sprintf('db:%s:%s:', $this->getModel()->getConnectionName(), $this->getModel()->getTable());

        $this->query->queryCache($cache);

        return $this;
    }

    /**
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param string|null $key
     * @param bool $nullable false:如果缓存有读取到，且值为 null，则不再继续读数据库
     * @return static
     */
    public function modelCache($driver = null, $ttl = null, $key = null, $nullable = false)
    {
        if(!$driver) {
            $driver = method_exists($this->getModel(), 'cacheDriver') ? $this->getModel()->cacheDriver() : (
                $this->getModel()->cacheDriver ?? config('cache.default')
            );
        }

        $cache = new Cache($driver, $ttl, $key, $nullable);
        $cache->prefix = sprintf('db:%s:%s:', $this->getModel()->getConnectionName(), $this->getModel()->getTable());

        $cache->prev = $this->cache;
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Model[]|static[]
     */
    public function getModels($columns = ['*'])
    {
        if(!$this->cache) {
            return parent::getModels($columns);
        }

        return $this->cache->remember(
            function() use($columns) {
                $results = parent::getModels($columns);dump($results);
                return count($results) === 0 ? null : $results;
            },
            md5($this->getQuery()->sql() . (is_string($columns) ? $columns : implode('-', $columns)))
        ) ?? [];
    }
}