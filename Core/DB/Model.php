<?php

namespace Core\DB;

use DB;
use Core\Support\Str;

class Model
{
    protected string $table = '';
    protected $attr;

    public function __construct($attr = null)
    {
        $this->attr = $this->attr ?? $attr ?? new \stdClass();

        if (!$this->table) {
            $this->table = self::identifyTableName();
        }
    }

    public static function identifyTableName()
    {
        $caller = strtolower(Str::lastPart(static::class, '\\'));

        return Str::plural($caller);
    }

    public function __get($key)
    {
        return $this->attr->{$key} ?? null;
    }

    public function __set($key, $value)
    {
        if (is_null($this->attr)) {
            $this->attr = new \stdClass;
        }

        return $this->attr->{$key} = $value;
    }

    public static function __callStatic(string $method, array $args)
    {
        return DB::table(self::identifyTableName(), static::class)->$method(...$args);
    }
}
