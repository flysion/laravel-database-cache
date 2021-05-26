<?php

namespace Flysion\Database;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Cacheable
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param string $key
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
     * @return string
     */
    public function scopeCacheKey($builder, $key)
    {
        return sprintf('db:%s:%s:%s', $this->getConnectionName(), $this->getTable(), $key);
    }
}