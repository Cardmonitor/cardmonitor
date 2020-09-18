<?php

namespace App\APIs\Skryfall;

use App\APIs\Skryfall\CardCollection as Collection;
use App\Apis\ApiModel;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\App;

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

        $model = new self();
        $model->fill($attributes);

        return $model;
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
        return implode(', ', $this->attributes['colors']);
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