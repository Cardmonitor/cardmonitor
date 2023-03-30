<?php

namespace App\Importers\Articles;

use App\Models\Cards\Card;
use Illuminate\Support\Arr;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Models\Localizations\Language;

class WooCommerceOrderImporter
{
    CONST SOURCE_SLUG = 'woocommerce-api';

    private array $articles = [];
    private int $user_id;

    private float $additional_unit_cost = 0;
    private float $bonus = 0;
    private int $source_sort = 1;

    private Storage $storage;


    public static function import(int $user_id, array $woocommerce_order): void
    {
        $importer = new self($user_id, $woocommerce_order);
        $importer->importOrder($woocommerce_order);
    }

    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function importOrder(array $woocommerce_order): array
    {
        $this->setBonus($woocommerce_order);
        $this->setStorage($woocommerce_order);
        $this->setAdditionalUnitCost($woocommerce_order);

        foreach ($woocommerce_order['line_items'] as $line_item) {

            if (strpos($line_item['sku'], '-') === false) {
                continue;
            }

            $this->importLineItem($line_item);
        }

        return $this->articles;
    }

    public function setAdditionalUnitCost(array $woocommerce_order): void
    {
        $article_count = 0;
        $additional_costs = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            if (strpos($line_item['sku'], '-') === false) {
                $additional_costs += $line_item['total'];
            }
            else {
                $article_count += $line_item['quantity'];
            }
        }
        $additional_costs *= 1 + $this->bonus;
        $this->additional_unit_cost = $additional_costs / $article_count;
    }

    public function setBonus(array $woocommerce_order): void
    {
        $this->bonus = ($woocommerce_order['payment_method'] == 'cod') ? 0.15 : 0;
    }

    public function setStorage(array $woocommerce_order): void
    {
        $storage_woocommerce = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => 'WooCommerce',
        ]);

        $this->storage = Storage::firstOrCreate([
            'user_id' => $this->user_id,
            'name' => 'Bestellung #' . $woocommerce_order['id'],
            'parent_id' => $storage_woocommerce->id,
        ]);
    }


    public function importLineItem(array $line_item): void
    {
        [$cardmarket_product_id, $is_foil] = explode('-', $line_item['sku']);
        $card = Card::firstOrImport($cardmarket_product_id);

        $language = Arr::first($line_item['meta_data'], function ($meta) {
            return str_starts_with($meta['key'], 'sprache');
        });

        $condition = Arr::first($line_item['meta_data'], function ($meta) {
            return $meta['key'] == 'zustand';
        });

        $unit_cost = $line_item['total'] / $line_item['quantity'] * (1 + $this->bonus) + $this->additional_unit_cost;

        for ($index=1; $index <= $line_item['quantity']; $index++) {
            $values = [
                'user_id' => $this->user_id,
                'card_id' => $card->id,
                'language_id' => Language::getIdByGermanName($language['value']),
                'cardmarket_article_id' => null,
                'condition' => array_search(substr($condition['value'], 0, strrpos($condition['value'], ' ')), Article::CONDITIONS),
                'unit_price' => $unit_cost * 3,
                'unit_cost' => $unit_cost,
                'sold_at' => null,
                'is_in_shoppingcard' => false,
                'is_foil' => ($is_foil == 'true'),
                'is_signed' => false,
                'is_altered' => false,
                'is_playset' => false,
                'cardmarket_comments' => null,
                'has_sync_error' => false,
                'sync_error' => null,
                'storage_id' => $this->storage->id,
                'source_sort' => $this->source_sort,
            ];
            $attributes = [
                'source_slug' => 'woocommerce-api',
                'source_id' => $line_item['id'],
                'index' => $index,
            ];

            $this->articles[] = Article::updateOrCreate($attributes, $values);

            $this->source_sort++;
        }
    }
}