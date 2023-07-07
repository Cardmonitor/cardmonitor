<?php

namespace App\Transformers\Articles\Csvs;

class PCG
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
            'is_foil' => false,
            'is_reverse_holo' => ($data[9] == 'X' ? true : false),
            'is_first_edition' => ($data[11] == 'X' ? true : false),
            'is_signed' => ($data[10] == 'X' ? true : false),
            'is_altered' => ($data[13] == 'X' ? true : false),
            'is_playset' => ($data[12] == 'X' ? true : false),
            'cardmarket_comments' => $data[14],
            'amount' => $data[15],
            'has_sync_error' => false,
            'sync_error' => null,
        ];
    }
}