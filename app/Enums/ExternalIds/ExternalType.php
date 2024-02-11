<?php

namespace App\Enums\ExternalIds;

enum ExternalType: string {
    case CARDMARKET = 'cardmarket';
    case WOOCOMMERCE = 'woocommerce';

    public function name(): string
    {
        return match ($this) {
            self::CARDMARKET => 'Cardmarket',
            self::WOOCOMMERCE => 'WooCommerce',
        };
    }
}

?>
