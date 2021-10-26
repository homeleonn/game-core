<?php

namespace Core\DB;

use Core\Contracts\Database\Database;

class DB extends MySQL implements Database
{
    public function table(string $tableName, $model = null): QueryBuilder
    {
        return new QueryBuilder($this, $tableName, $model);
    }
}
