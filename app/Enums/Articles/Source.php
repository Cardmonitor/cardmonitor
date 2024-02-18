<?php

namespace App\Enums\Articles;

enum Source: string {
    case CARDMARKET_ORDER = 'cardmarket-order';
    case WOOCOMMERCE_PURCHASE = 'woocommerce-api';
    case MAGIC_SORTER = 'magic-sorter';
    case TCG_POWERTOOLS = 'tcg-powertools';
}



?>
