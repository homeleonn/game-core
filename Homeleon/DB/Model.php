<?php

namespace Homeleon\DB;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Str;

class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected $attr = [];
    protected $original = [];

    public function __construct(?array $attr = null)
    {
        if (!empty($attr)) {
            $this->attr = $attr;
        }

        if ($this->attr) {
            $this->fillOriginalValues();
        }

        if (!$this->table) {
            $this->table = self::identifyTableName();
        }
    }

    private function fillOriginalValues(?array $values = null)
    {
        foreach ($values ?? $this->attr as $k => $v) {
            $this->original[$k] = &$this->attr[$k];
        }
    }

    protected function getTableName()
    {
        return $this->table;
    }

    public static function identifyTableName(): string
    {
        $defaultTableName = (new \ReflectionProperty(static::class, 'table'))->getDefaultValue();
        if ($defaultTableName) {
            return $defaultTableName;
        }

        $caller = strtolower(Str::lastPart(static::class, '\\'));

        return Str::plural($caller);
    }

    public function getId(): mixed
    {
        return $this->attr[$this->primaryKey] ?? null;
    }

    public function update(array $values): void
    {
        $this->attr = array_merge($this->attr, $values);

        $this->save();
    }

    public function delete()
    {
        if ($id = $this->getId()) {
            return DB::query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = {$id} LIMIT 1");
        }

        return false;
    }

    public function save(): ?static
    {
        // dd($this->attr, $this->original);
        $insert = array_diff_assoc($this->attr, $this->original);

        if (empty($insert)) {
            return null;
        }

        $this->fillOriginalValues($insert);
        $id         = $this->getId();
        $isExists   = $id ? DB::table($this->table)->count()->where($this->primaryKey, $id)->first() : false;

        if ($isExists) {
            $set = $this->prepareSet($insert);
            $query = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = {$id}";
        } else {
            [$insertColumns, $preparedValues, $onDuplicate] = $this->prepareInsert($insert);
            $query = "INSERT INTO {$this->table} ({$insertColumns}) VALUES ({$preparedValues}) ON DUPLICATE KEY UPDATE {$onDuplicate}";
        }

        DB::query($query);

        if (!$isExists && $id = DB::insertId()) {
            $this->setAttributes(static::find($id)->getAttributes());
        }

        return $this;
    }

    private function prepareSet(array $insert): string
    {
        $s = '';
        foreach ($insert as $key => $value) {
            if ($key == $this->primaryKey) continue;
            $value = DB::escapeString($value);
            $s .= "{$key} = {$value}, ";
        }

        return substr($s, 0, -2);
    }

    private function prepareInsert($insert): array
    {
        $insertColumns = implode(', ', array_keys($insert));
        $preparedValues = implode(', ', array_map(function ($value) {
            return DB::escapeString($value);
        }, $insert));
        $onDuplicate = $this->prepareSet($insert);

        return [$insertColumns, $preparedValues, $onDuplicate];
    }

    public function only(...$fields)
    {
        $only = [];

        foreach ($fields as $key) {
            $only[$key] = $this->attr[$key];
        }

        return $only;
    }

    public function getAttributes()
    {
        return $this->attr;
    }

    public function setAttributes($attr)
    {
        $this->attr = $attr;
        $this->fillOriginalValues();
    }

    public function __get($key)
    {
        return $this->attr[$key] ?? null;
    }

    public function __set($key, $value)
    {
        if (isset($this->attr[$key])) unset($this->attr[$key]);

        $mutatorName = 'set'.ucfirst($key).'Attr';
        if (method_exists([$this, $mutatorName])) {
            return $this->{$mutatorName}($value);
        }

        return $this->attr[$key] = $value;
    }

    public function __isset($key)
    {
        return isset($this->attr[$key]);
    }

    public function __unset($key)
    {
        unset($this->attr[$key]);
    }

    public function __call($method, $args)
    {
        return DB::table($this->table, $this::class)->$method(...$args);
    }

    public static function __callStatic(string $method, array $args)
    {
        return DB::table(self::identifyTableName(), static::class)->$method(...$args);
    }
}
