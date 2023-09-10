<?php

namespace App\APIs\WooCommerce;

enum Status: string {
    case ANY = 'any';
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case ON_HOLD = 'on-hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
    case TRASH = 'trash';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}