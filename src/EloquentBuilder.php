<?php

namespace Flysion\Database;

class EloquentBuilder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * @var Cache
     */
    public $cache;

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return Cache
     */
    protected function newCache($refreshTtl = null, $ttl = null, $allowNull = false, $driver = null)
    {
        $model = $this->getModel();

        if(is_null($driver)) {
            if(method_exists($model, 'cacheDriver')) {
                $driver = $model->cacheDriver();
            } else {
                $driver = $model->cacheDriver ?? null;
            }
        }

        $prefix = sprintf('db:%s:%s:', $model->getConnectionName(), $model->getTable());

        return new Cache(
            $refreshTtl,
            $ttl,
            $allowNull,
            $driver,
            $prefix
        );
    }

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @param bool $useModelCache
     * @return static
     */
    public function cache($refreshTtl = null, $ttl = null, $allowNull = false, $driver = null, $useModelCache = false)
    {
        $object = $useModelCache ? $this : $this->getQuery();

        if(!$object->cache) {
            $object->cache = $this->newCache($refreshTtl, $ttl, $allowNull, $driver);
        } else {
            $object->cache = $object->cache->next($refreshTtl, $ttl, $allowNull, $driver);
        }

        return $this;
    }

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param bool $useModelCache
     * @return static
     */
    public function cacheFromArray($refreshTtl = null, $ttl = null, $allowNull = false, $useModelCache = false)
    {
        return $this->cache($refreshTtl, $ttl, $allowNull, 'array', $useModelCache);
    }

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param bool $useModelCache
     * @return static
     */
    public function cacheFromFile($refreshTtl = null, $ttl = null, $allowNull = false, $useModelCache = false)
    {
        return $this->cache($refreshTtl, $ttl, $allowNull, 'file', $useModelCache);
    }

    /**
     * @param $key
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @param bool $useModelCache
     * @return static
     */
    public function whereKeyWithCache($key, $refreshTtl = null, $ttl = null, $allowNull = false, $driver = null, $useModelCache = false)
    {
        return $this->whereKey($key)->cache($refreshTtl, $ttl, $allowNull, $driver, $useModelCache);
    }

    /**
     * @param mixed $where
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @param bool $useModelCache
     * @return static
     */
    public function whereWithCache($where, $refreshTtl = null, $ttl = null, $allowNull = false, $driver = null, $useModelCache = false)
    {
        return $this->where($where)->cache($refreshTtl, $ttl, $allowNull, $driver, $useModelCache);
    }

    /**
     * Get the hydrated models without eager loading.
     *
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Model[]|static[]
     * @throws
     */
    public function getModels($columns = ['*'])
    {
        if(!isset($this->cache)) {
            return parent::getModels($columns);
        }

        $sql = $this->getQuery()->onceWithColumns($columns, function() {
            return sql($this->getQuery()->toSql(), $this->getQuery()->getBindings());
        });

        return $this->cache->remember(function() use($columns) {
           $result = parent::getModels($columns);
           return count($result) === 0 ? null : $result;
        }, md5($sql)) ?? [];
    }
}