<?php

namespace App\Enums\Orders;

enum Status: string {
    case BOUGHT = 'bought';
    case CANCELLED = 'cancelled';
    case EVALUATED = 'evaluated';
    case LOST = 'lost';
    case PAID = 'paid';
    case RECEIVED = 'received';
    case SENT = 'sent';

    public static function fromWooCommerceSlug(string $slug): self
    {
        return match ($slug) {
            'pending' => self::BOUGHT,
            'processing' => self::PAID,
            'on-hold' =>  self::BOUGHT,
            'completed' => self::SENT,
            'cancelled' => self::CANCELLED,
            'refunded' => self::CANCELLED,
            'failed' => self::LOST,
        };
    }

    public static function filter(): array
    {
        return collect(self::cases())->mapWithKeys(fn($value) => [$value->value => $value->name()])->toArray();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function name(): string
    {
        return match ($this) {
            self::BOUGHT => 'Unbezahlt',
            self::PAID => 'Bezahlt',
            self::SENT => 'Versandt',
            self::RECEIVED => 'Angekommen',
            self::EVALUATED => 'Bewertet',
            self::LOST => 'Nicht Angekommen',
            self::CANCELLED => 'Storniert',
        };
    }

    public function woocommerceSlug(): string
    {
        return match ($this) {
            self::BOUGHT => 'pending',
            self::PAID => 'processing',
            self::SENT => 'completed',
            self::CANCELLED => 'cancelled',
            self::LOST => 'failed',
            self::RECEIVED => 'completed',
            self::EVALUATED => 'completed',
        };
    }
}



?>
