<?php

namespace Flysion\Database;

class QueryBuilder extends \Illuminate\Database\Query\Builder
{
    /**
     * @var Cache;
     */
    public $cache;

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return static
     */
    public function cache($refreshTtl = null, $ttl = null, $allowNull = false, $driver = null)
    {
        if(!$this->cache) {
            $this->cache = new Cache(
                $refreshTtl, $ttl, $allowNull, $driver, 'db:'
            );
        } else {
            $this->cache = $this->cache->next($refreshTtl, $ttl, $allowNull, $driver);
        }

        return $this;
    }

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @return static
     */
    public function cacheFromArray($refreshTtl = null, $ttl = null, $allowNull = false)
    {
        return $this->cache($refreshTtl, $ttl, $allowNull, 'array');
    }

    /**
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @return static
     */
    public function cacheFromFile($refreshTtl = null, $ttl = null, $allowNull = false)
    {
        return $this->cache($refreshTtl, $ttl, $allowNull, 'file');
    }

    /**
     * @param mixed $where
     * @param int|null $refreshTtl
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param bool $allowNull
     * @param string|\Illuminate\Contracts\Cache\Repository|null $driver
     * @return static
     */
    public function whereWithCache($where, $refreshTtl = null, $ttl = null, $allowNull = false, $driver = null)
    {
        return $this->where($where)->cache($refreshTtl, $ttl, $allowNull, $driver);
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return mixed
     * @throws
     */
    protected function runSelect()
    {
        if(!$this->cache) {
            return parent::runSelect();
        }

        $sql = sql($this->toSql(), $this->getBindings());

        return $this->cache->remember(function() {
            $result = parent::runSelect();
            return count($result) === 0 ? null : $result;
        }, md5($sql)) ?? [];
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param  array  $columns
     * @param  callable  $callback
     * @return mixed
     */
    public function onceWithColumns($columns, $callback)
    {
        return parent::onceWithColumns($columns, $callback);
    }
}