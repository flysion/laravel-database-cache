<?php

namespace Flysion\Database;

/**
 * @param $sql
 * @param $bindings
 * @return string
 */
function sql($sql, $bindings)
{
    return sprintf(str_replace('?', '%s', $sql), ...$bindings);
}