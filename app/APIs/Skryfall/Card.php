<?php

namespace App\APIs\Skryfall;

use App\APIs\ApiModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use GuzzleHttp\Exception\ClientException;
use App\APIs\Skryfall\CardCollection as Collection;

class Card extends ApiModel
{
    public $timestamps = false;

    protected $dates = [
        'released_at',
    ];

    protected $dateFormat = 'Y-m-d H:i:s';

    public static function find(string $id)
    {

    }

    public static function findByCardmarketId(int $cardmarket_id)
    {
        $api = App::make('SkryfallApi');

        try {
            $attributes = $api->card->findByCardmarketId($cardmarket_id);
            $model = new self();
            $model->fill($attributes);

            return $model;
        }
        catch(ClientException $e) {
            return null;
        }
    }

    public static function findByCodeAndNumber(string $code, int $number)
    {
        $api = App::make('SkryfallApi');

        try {
            $attributes = $api->card->findByCodeAndNumber($code, $number);

            $model = new self();
            $model->fill($attributes);

            return $model;
        }
        catch(ClientException $e) {
            return null;
        }
    }

    public static function search(array $parameters) : Collection
    {
        $api = App::make('SkryfallApi');

        $collection = new Collection();

        $parameters['page'] = $parameters['page'] ?? 1;
        do {
            $data = $api->card->search($parameters);
            foreach ($data['data'] as $key => $attributes) {
                $model = new self();
                $model->fill($attributes);

                $collection->push($model);
            }
            $parameters['page']++;
        }
        while ($data['has_more']);

        return $collection;
    }

    public static function fromSet(string $code) : Collection
    {
        return self::search([
            'order' => 'set',
            'q' => 'set:' . $code,
            'unique' => 'prints',
        ]);
    }

    public function getColorsStringAttribute() : string
    {
        return implode(', ', $this->attributes['colors'] ?? []);
    }

    public function getColorOrderByAttribute(): string
    {
        // Land
        if ($this->type_line == 'Land') {
            return 'L';
        }

        if (! is_array($this->colors)) {
            return '';
        }

        $count = count($this->colors);

        // Multicolor
        if ($count > 1) {
            return 'M';
        }

        // Colorless
        if ($count == 0) {
            return 'C';
        }

        // Color
        if ($count == 1) {
            return Arr::get($this->colors, 0, '');
        }

        return '';
    }

    public function getColorsAttribute(): array
    {
        if (Arr::has($this->attributes, 'colors')) {
            return $this->attributes['colors'];
        }

        if (Arr::has($this->attributes, 'card_faces.0.colors')) {
            return $this->attributes['card_faces'][0]['colors'];
        }

        return [];
    }

    public function getImageUrisAttribute(): array
    {
        if (Arr::has($this->attributes, 'image_uris')) {
            return $this->attributes['image_uris'];
        }

        if (Arr::has($this->attributes, 'card_faces.0.image_uris')) {
            return $this->attributes['card_faces'][0]['image_uris'];
        }

        return [];
    }

    public function getColorIdentityStringAttribute() : string
    {
        return implode(', ', $this->attributes['color_identity']);
    }

    public function getLegalitiesStandardAttribute() : string
    {
        return $this->legalities['standard'];
    }

    public function getLegalitiesFutureAttribute() : string
    {
        return $this->legalities['future'];
    }

    public function getLegalitiesHistoricAttribute() : string
    {
        return $this->legalities['historic'];
    }

    public function getLegalitiesPioneerAttribute() : string
    {
        return $this->legalities['pioneer'];
    }

    public function getLegalitiesModernAttribute() : string
    {
        return $this->legalities['modern'];
    }

    public function getLegalitiesLegacyAttribute() : string
    {
        return $this->legalities['legacy'];
    }

    public function getLegalitiesPauperAttribute() : string
    {
        return $this->legalities['pauper'];
    }

    public function getLegalitiesVintageAttribute() : string
    {
        return $this->legalities['vintage'];
    }

    public function getLegalitiesPennyAttribute() : string
    {
        return $this->legalities['penny'];
    }

    public function getLegalitiesCommanderAttribute() : string
    {
        return $this->legalities['commander'];
    }

    public function getLegalitiesBrawlAttribute() : string
    {
        return $this->legalities['brawl'];
    }

    public function getLegalitiesDuelAttribute() : string
    {
        return $this->legalities['duell'];
    }

    public function getLegalitiesOldschoolAttribute() : string
    {
        return $this->legalities['oldschool'];
    }

    public function getImageUriSmallAttribute() : string
    {
        return $this->image_uris['small'] ?? '';
    }

    public function getImageUriNormalAttribute() : string
    {
        return $this->image_uris['normal'] ?? '';
    }

    public function getImageUriLargeAttribute() : string
    {
        return $this->image_uris['large'] ?? '';
    }

    public function getImageUriPngAttribute() : string
    {
        return $this->image_uris['png'] ?? '';
    }
}
?>