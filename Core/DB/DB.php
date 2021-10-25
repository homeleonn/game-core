<?php

namespace Core\DB;

class DB extends MySQL
{
    public function table(string $tableName, $model = null): QueryBuilder
    {
        return new QueryBuilder($this, $tableName, $model);
    }
}
