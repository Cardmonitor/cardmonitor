<?php

namespace Tests\Unit\Exporters\Orders;

use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use App\Importers\Articles\WooCommerceOrderImporter;

class WooCommerceOrderImporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_update_or_create_an_order_from_woocommerce_api()
    {
        $path_order = 'tests/snapshots/woocommerce/orders/619687.json';
        $woocommerce_order = json_decode(file_get_contents($path_order), true);

        $storage_woocommerce = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'WooCommerce',
        ]);

        $storage_order = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'Bestellung #' . $woocommerce_order['id'],
            'parent_id' => $storage_woocommerce->id,
        ]);

        $quantity = 0;
        foreach ($woocommerce_order['line_items'] as $line_item) {
            factory(Card::class)->create([
                'game_id' => Game::ID_MAGIC,
                'cardmarket_product_id' => trim($line_item['sku'], '-'),
            ]);

            $quantity += $line_item['quantity'];
        }

        WooCommerceOrderImporter::import($this->user->id, $woocommerce_order);

        $this->assertCount($quantity, Article::all());
        $this->assertCount($quantity, $storage_order->articles);

        $source_sort = 1;
        foreach (Article::all() as $key => $article) {
            $line_item = $woocommerce_order['line_items'][$key];

            $this->assertEquals(WooCommerceOrderImporter::SOURCE_SLUG, $article->source_slug);
            $this->assertEquals($line_item['id'], $article->source_id);
            $this->assertEquals($source_sort, $article->source_sort);
            $this->assertEquals(trim($line_item['sku'], '-'), $article->card_id);
            $this->assertEquals($line_item['total'] * (1 + 0.15), $article->unit_cost);
            $this->assertEquals($line_item['total'] * (1 + 0.15) * 3, $article->unit_price);
            $this->assertEquals('NM', $article->condition);
            $this->assertEquals(false, $article->is_foil);
            $this->assertEquals(\App\Models\Localizations\Language::DEFAULT_ID, $article->language_id);

            $source_sort++;
        }
    }
}