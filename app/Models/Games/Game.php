<?php

namespace App\Models\Games;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Game extends Model
{
    const ID_MAGIC = 1;
    const ID_YUGIOH = 3;
    const ID_POKEMON = 6;
    const ID_FLESH_AND_BLOOD = 16;

    public $incrementing = false;

    protected $guarded = [];

    public static function importables() : Collection
    {
        return self::where('is_importable', true)->get();
    }

    public static function keyValue() : Collection
    {
        return self::importables()->mapWithKeys(function ($item) {
           return [$item['id'] => $item['name']];
        });
    }

    public static function classnames(string $namespace) : Collection
    {
        return self::importables()->mapWithKeys(function ($item) use ($namespace) {
           return [$item['id'] => $namespace . '\\' . $item['abbreviation']];
        });
    }
}
