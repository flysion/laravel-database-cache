<?php

namespace Flysion\Database;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Cacheable
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  QueryBuilder  $query
     * @return EloquentBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new EloquentBuilder($query);
    }
}