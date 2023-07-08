<?php

namespace App\Transformers\Articles\Csvs;

use App\Models\Games\Game;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;

class Transformer
{
    private array $header = [];

    public function setHeader(array $header): void {
        $this->header = $header;
    }

    public function unify(array $row) {

        $data = array_combine(array_keys($this->header), $row);

        return [
            'amount' => Arr::get($data, 'amount'),
            'card_id' => Arr::get($data, 'idproduct'),
            'cardmarket_article_id' => Arr::get($data, 'idarticle'),
            'cardmarket_comments' => Arr::get($data, 'comments'),
            'condition' => Arr::get($data, 'condition'),
            'has_sync_error' => false,
            'is_altered' => Arr::get($data, 'altered', '') == 'X' ? true : false,
            'is_first_edition' => Arr::get($data, 'firstedition', '') == 'X' ? true : false,
            'is_foil' => Arr::get($data, 'foil', '') == 'X' ? true : false,
            'is_in_shoppingcard' => false,
            'is_playset' => Arr::get($data, 'playset', '') == 'X' ? true : false,
            'is_reverse_holo' => Arr::get($data, 'reverseholo', '') == 'X' ? true : false,
            'is_signed' => Arr::get($data, 'signed', '') == 'X' ? true : false,
            'language_id' => Arr::get($data, 'language'),
            'number_from_cardmarket_comments' => Article::numberFromCardmarketComments(Arr::get($data, 'comments')),
            'sold_at' => null,
            'sync_error' => null,
            'unit_price' => Arr::get($data, 'price'),
        ];
    }

    public static function transform(int $gameId, array $data) : array
    {
        $transformer = self::transformer($gameId);
        return $transformer::transform($data);
    }

    public static function transformer(int $gameId)
    {
        $games = Game::classnames('App\Transformers\Articles\Csvs');
        if (! Arr::has($games, $gameId)) {
            throw new \InvalidArgumentException('Game ID "' . $gameId . '" is not available. Create Transformer!');
        }

        $classname = Arr::get($games, $gameId);

        return new $classname();
    }
}