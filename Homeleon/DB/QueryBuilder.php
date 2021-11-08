<?php

namespace Homeleon\DB;

class QueryBuilder
{
    protected array $builder;
    protected $result;

    public function __construct(
        protected $connection,
        string $tableName,
        protected ?string $model
    )
    {
        $this->builder['table'] = $tableName;

        return $this;
    }

    public function as($tableAlias): self
    {
        $this->builder['table_alias'] = $tableAlias;

        return $this;
    }

    public function count(): self
    {
        $this->select();
        $this->builder['count'] = true;

        return $this;
    }

    public function where($field, $value): self
    {

        $this->builder['where'][$field] = $value;

        return $this;
    }

    public function andWhere($field, $value): self
    {
        $this->builder['and_where'][$field] = $value;

        return $this;
    }

    public function orWhere($field, $value): self
    {
        $this->builder['or_where'][$field] = $value;

        return $this;
    }

    public function whereIn(string $field, array $values)
    {
        $this->builder['where_in'][$field] = $values;

        return $this;
    }

    public function orderBy($field, $order = 'ASC'): self
    {
        $this->builder['order_by'][$field] = $order;

        return $this;
    }

    public function limit($offset, $count = null): self
    {
        $this->builder['limit'] = " LIMIT {$offset}" . ($count ? ", {$count}" : '');

        return $this;
    }

    public function select(...$fields): self
    {
        $this->builder['fields'] = $fields;

        return $this;
    }

    public function get(array $fields = []): string
    {
        $table      = isset($this->builder['table_alias'])
                    ? "{$this->builder['table']} as {$this->builder['table_alias']}"
                    : "{$this->builder['table']}";
        $fields     = $this->prepareFields($this->builder['fields'] ?? ($fields ?: null));
        $where      = $this->prepareConditions();

        $this->result = "SELECT {$fields} FROM {$table}{$where}";

        return $this->result;
    }

    private function getLimit()
    {
        return $this->builder['limit'] ?? '';
    }

    public function first()
    {
        $this->limit(1);

        return $this->query('Row');
    }

    public function all()
    {
        return $this->query('All');
    }

    public function find($id)
    {
        $this->where('id', $id);

        return $this->first();
    }

    public function by($column)
    {
        $this->builder['by'] = $column;

        return $this;
    }

    public function insert(array $strings)
    {
        $fields = implode(', ', array_keys($strings[0]));

        $values = [];
        foreach ($strings as $string) {
            $values[] = implode(', ', array_map(
                fn ($value) => $this->connection->escapeString($value),
                array_values($string)
            ));
        }
        $values = implode('), (', $values);

        $query = "INSERT INTO {$this->builder['table']} ({$fields}) VALUES ({$values})";
        $this->connection->query($query);

        // dd($query);
    }

    public function update(array $values)
    {
        $where = $this->prepareConditions();
        $set  = substr($this->join($values, ', '), 1);
        $q    = "UPDATE {$this->builder['table']} SET
                {$set}
            $where";
        // dd($q);
        $this->connection->query($q);
    }

    public function delete()
    {
        $where = $this->prepareConditions();
        $q = "DELETE FROM {$this->builder['table']}{$where}";
        $this->connection->query($q);
    }

    private function prepareConditions(): string
    {
        return $this->join('where', ' WHERE ')
             . $this->join('and_where', ' AND ')
             . $this->join('or_where', ' OR ')
             . $this->in()
             . $this->prepareOrderBy($this->builder['order_by'] ?? null)
             . $this->getLimit();
    }

    private function in()
    {
        if (!isset($this->builder['where_in'])) return '';

        $where = isset($this->builder['where']) ? ' AND ' : ' WHERE ';
        $s = '';
        foreach ($this->builder['where_in'] as $field => $values) {
            $values = implode(', ', $this->escapeArr($values));
            $s .= "{$where}{$field} IN({$values})";
        }

        return $s;
    }

    public function query(string $type)
    {
        $this->connection->setModel($this->model);

        if (isset($this->builder['by'])) {
            return $this->connection->{"getInd"}($this->builder['by'], $this->getResult());
        }

        return $this->connection->{"get{$type}"}($this->getResult());
    }

    public function getResult()
    {
        return $this->result ?? $this->get();
    }

    public function escapeArr(array $values)
    {
        $result = [];
        foreach ($values as $key => $value) {
            $result[$key] = $this->connection->escapeString($value);
        }
        return $result;
    }

    public function join($builderKey, $sep = '', $equals = '=', $tableName = true): string
    {
        $values = is_string($builderKey) ? ($this->builder[$builderKey] ?? null) : $builderKey;
        if (!isset($values)) return '';

        $preparedValues = $this->escapeArr($values);
        $tableName = $tableName ? '`' . $this->getTableName() . '`.' : '';

        $s = '';
        foreach ($preparedValues as $key => $value) {
            $s = "{$sep}{$key} {$equals} {$value}";
        }

        return $s;
    }

    public function prepareFields($fields): string
    {
        $tableName = $this->getTableName();
        return $fields ? implode(", ", $this->builder['fields']) : (isset($this->builder['count']) ? 'count(*)' : '*');
    }

    public function getTableName(): string
    {
        return $this->builder['table_alias'] ?? $this->builder['table'];
    }

    public function prepareOrderBy($orderBy): string
    {
        return is_null($orderBy) ? '' : ' ORDER BY ' . key($orderBy) . ' ' . current($orderBy);
    }
}
