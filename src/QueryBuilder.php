<?php

namespace Flysion\Database;

class QueryBuilder extends \Illuminate\Database\Query\Builder
{
    /**
     * @var Cache;
     */
    protected $cache;

    /**
     * @return static|Cache
     */
    public function queryCache()
    {
        $arguments = func_get_args();

        if(count($arguments) === 1 && $arguments[0] instanceof Cache)
        {
            $cache = $arguments[0];
        }

        $cache->prev = $this->cache;
        $this->cache = $cache;

        return $this;
    }

    /**
     * get sql
     *
     * @return string
     */
    public function sql()
    {
        $bindings = $this->getBindings();
        $sql = str_replace('?', '%s', $this->toSql());
        return sprintf($sql, ...$bindings);
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

        return $this->cache->remember(function() {
            $results = parent::runSelect();
            return count($results) === 0 ? null : $results;
        }, md5($this->sql()));
    }
}