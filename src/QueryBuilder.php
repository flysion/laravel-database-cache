<?php

namespace Flysion\Database;

class QueryBuilder extends \Illuminate\Database\Query\Builder
{
    /**
     * @var Cache;
     */
    public $cache;

    /**
     * @param array $options
     * @return static
     */
    public function cache($options = [])
    {
        $options = array_merge([
            'refresh_ttl' => null,
            'ttl' => null,
            'allow_null' => false,
            'driver' => null,
        ], $options);

        if(!$this->cache) {
            $this->cache = new Cache(
                $options['refresh_ttl'],
                $options['ttl'],
                $options['allow_null'],
                $options['driver'],
                'db:'
            );
        } else {
            $this->cache = $this->cache->next(
                $options['refresh_ttl'],
                $options['ttl'],
                $options['allow_null'],
                $options['driver']
            );
        }

        return $this;
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