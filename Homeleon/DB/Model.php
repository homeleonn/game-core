<?php

namespace Homeleon\DB;

use Homeleon\Support\Facades\DB;
use Homeleon\Support\Str;

class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected $attr;
    protected $original = [];

    public function __construct(?array $attr = null)
    {
        if ($this->attr) {
            $this->original = $this->attr;
        } else {
            $this->attr = $attr ?? [];
        }

        if (!$this->table) {
            $this->table = self::identifyTableName();
        }
    }

    public static function identifyTableName(): string
    {
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
            return DB::query("DELETE FROM {$this->table} WHERE {$this->primaryKey} = {$id}");
        }

        return false;
    }

    public function save(): ?static
    {
        $insert = array_diff_assoc($this->attr, $this->original);

        if (empty($insert)) {
            return null;
        }

        $id         = $this->getId();
        $isExists   = $id ? static::count()->where($this->primaryKey, $id)->first() : false;

        if ($isExists) {
            $set = $this->prepareSet($insert);
            $query = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = {$id}";
        } else {
            [$insertColumns, $preparedValues, $onDuplicate] = $this->prepareInsert($insert);
            $query = "INSERT INTO {$this->table} ({$insertColumns}) VALUES ({$preparedValues}) ON DUPLICATE KEY UPDATE {$onDuplicate}";
        }

        DB::query($query);

        if (!$isExists && $id = DB::insertId()) {
            return static::find($id);
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

    public function __get($key)
    {
        return $this->attr[$key] ?? null;
    }

    public function __set($key, $value)
    {
        if (is_null($this->attr)) {
            $this->attr = [];
        }

        return $this->attr[$key] = Str::toNum($value);
    }

    public function __call(string $method, array $args)
    {
        return DB::table($this->table, $this::class)->$method(...$args);
    }

    public static function __callStatic(string $method, array $args)
    {
        return DB::table(self::identifyTableName(), static::class)->$method(...$args);
    }
}
