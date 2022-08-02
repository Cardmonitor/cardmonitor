<?php

namespace App\Models\Localizations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    const DEFAULT_ID = 1;
    const DEFAULT_NAME = 'English';

    const GERMAN_TO_IDS = [
        1 => 'Englisch',
        2 => 'Französisch',
        3 => 'Deutsch',
        4 => 'Spanisch',
        5 => 'Italienisch',
        6 => 'S-Chinesisch',
        7 => 'Japanisch',
        8 => 'Portugiesisch',
        9 => 'Koreanisch',
        10 => 'T-Chinesisch',
    ];

    public $incrementing = false;

    protected $fillable = [
        'id',
        'code',
        'name',
    ];

    public static function getByCode(string $code) : self
    {
        $code = strtolower($code);

        return Cache::rememberForever('language.' . $code, function () use ($code) {
            return self::firstWhere('code', $code);
        });
    }

    public static function getIdByGermanName(string $german_name): int
    {
        $id = array_search($german_name, self::GERMAN_TO_IDS);

        if ($id === false) {
            return 0;
        }

        return $id;
    }

    public static function setup()
    {
        $languages = [
            1 => [
                'code' => 'gb',
                'name' => 'English',
            ],
            2 => [
                'code' => 'fr',
                'name' => 'French',
            ],
            3 => [
                'code' => 'de',
                'name' => 'German',
            ],
            4 => [
                'code' => 'es',
                'name' => 'Spanish',
            ],
            5 => [
                'code' => 'it',
                'name' => 'Italian',
            ],
            6 => [
                'code' => 'cn',
                'name' => 'Simplified Chinese',
            ],
            7 => [
                'code' => 'jp',
                'name' => 'Japanese',
            ],
            8 => [
                'code' => 'pt',
                'name' => 'Portuguese',
            ],
            9 => [
                'code' => 'ru',
                'name' => 'Russian',
            ],
            10 => [
                'code' => 'kr',
                'name' => 'Korean',
            ],
            11 => [
                'code' => 'cn',
                'name' => 'Traditional Chinese',
            ],
        ];
        foreach ($languages as $id => $language) {
            self::updateOrCreate([
                'id' => $id,
                'code' => $language['code'],
                'name' => $language['name'],
            ]);
        }
    }

}
