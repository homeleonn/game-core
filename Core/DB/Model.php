<?php

namespace Core\DB;

class Model
{
    protected string $table = '';
    public function __construct(
        private $attr = null
    ){
        $this->identifyTableName();
    }

    public function find(int $id): ?static
    {
        $model = \DB::getRow("SELECT * from `{$this->table}` where `id` = {$id} LIMIT 1");

        if (!$model) return null;

        $this->attr = $model;

        return $this;
    }

    private function identifyTableName()
    {
        if ($this->table) return;

        preg_match('/([^\\\]+)$/', static::class, $matches);
        $this->table = lcfirst($matches[1]);
        $this->table = $this->plural($this->table);
    }

    private function plural($word)
    {
        $lastLetter = substr($word, -1);

        if ($lastLetter == 'y') {
            return substr_replace($word, 'ies', -1);
        }

        return $word . 's';
    }

    public function __get($key)
    {
        return $this->attr->{$key} ?? null;
    }

    public function __set($key, $value)
    {
        return $this->attr->{$key} = $value;
    }
}
