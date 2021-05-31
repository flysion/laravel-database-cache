<?php

namespace Flysion\Database;

class EloquentBuilder extends \Illuminate\Database\Eloquent\Builder
{
    /**
     * @var Cache
     */
    public $cache;

    /**
     * @param array $options
     * @return Cache
     */
    protected function newCache($options)
    {
        $model = $this->getModel();

        if(is_null($options['driver'])) {
            if(method_exists($model, 'cacheDriver')) {
                $options['driver'] = $model->cacheDriver();
            } else {
                $options['driver'] = $model->cacheDriver ?? null;
            }
        }

        $prefix = sprintf('db:%s:%s:', $model->getConnectionName(), $model->getTable());

        return new Cache(
            $options['refresh_ttl'],
            $options['ttl'],
            $options['allow_null'],
            $options['driver'],
            $prefix
        );
    }

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
            'use_model_cache' => false,
        ], $options);

        $object = $options['use_model_cache'] ? $this : $this->getQuery();

        if($object->cache) {
            $object->cache = $object->cache->next(
                $options['refresh_ttl'],
                $options['ttl'],
                $options['allow_null'],
                $options['driver']
            );
        } else {
            $object->cache = $this->newCache($options);
        }

        return $this;
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