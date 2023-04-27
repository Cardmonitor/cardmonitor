<?php

namespace App\Transformers\Articles\Csvs;

use App\Models\Articles\Article;

class MtG
{
    public static function transform(array $data) : array
    {
        return [
            'card_id' => $data[1],
            'language_id' => $data[7],
            'cardmarket_article_id' => $data[0],
            'condition' => $data[8],
            'unit_price' => $data[6],
            'sold_at' => null,
            'is_in_shoppingcard' => false,
            'is_foil' => ($data[9] == 'X' ? true : false),
            'is_signed' => ($data[10] == 'X' ? true : false),
            'is_altered' => ($data[11] == 'X' ? true : false),
            'is_playset' => ($data[12] == 'X' ? true : false),
            'cardmarket_comments' => $data[13],
            'amount' => $data[14],
            'has_sync_error' => false,
            'sync_error' => null,
            'number_from_cardmarket_comments' => Article::numberFromCardmarketComments($data[13]),
        ];
    }
}