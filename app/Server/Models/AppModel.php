<?php

namespace App\Server\Models;

use JsonSerializable;
use Homeleon\DB\Model;
use Homeleon\Support\Str;

class AppModel extends Model implements JsonSerializable
{

    public function isBot()
    {
        return $this instanceof (Npc::class);
    }

    public function setAttrs(array $attrs, bool $selfPriority = true)
    {
        $this->attr = $selfPriority ? array_merge($attrs, $this->attr) : array_merge($this->attr, $attrs);
    }

    public function __set($key, $value)
    {
        if (isset($this->attr[$key])) unset($this->attr[$key]);
        return $this->attr[$key] = Str::toNum($value);
    }

    public function jsonSerialize()
    {
        return $this->attr;
    }
}
