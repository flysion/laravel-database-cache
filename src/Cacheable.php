<?php

namespace Flysion\Database;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Cacheable
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $key 缓存的KEY
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return Cache
     */
    public function scopeCache($builder, $key, $ttl = null, $allowNull = false, $driver = null)
    {
        return new Cache(
            $builder,
            $key,
            $ttl,
            $allowNull,
            $driver ?? $this->cacheDefaultDriver ?? null
        );
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $where where 条件
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return Cache
     */
    public function scopeWhereWithCache($builder, $where, $ttl = null, $allowNull = false, $driver = null)
    {
        return $builder->where($where)->cache(implode(':', $where), $ttl, $allowNull, $driver);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $key 数据库的主键
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return Cache
     */
    public function scopeWhereKeyWithCache($builder, $key, $ttl = null, $allowNull = false, $driver = null)
    {
        return $builder->whereKey($key)->cache($key, $ttl, $allowNull, $driver);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @return Cache
     */
    public function scopeCacheFromArray($builder, $key, $ttl = null, $allowNull = false)
    {
        return $this->cache($key, $ttl, $allowNull, 'array');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $key
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull 将 null 也缓存起来
     * @return Cache
     */
    public function scopeCacheFromFile($builder, $key, $ttl = null, $allowNull = false)
    {
        return $this->cache($key, $ttl, $allowNull, 'file');
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $key
     * @return string
     */
    public function scopeCacheKey($builder, $key)
    {
        return sprintf('db:%s:%s:%s', $this->getConnectionName(), $this->getTable(), $key);
    }
}