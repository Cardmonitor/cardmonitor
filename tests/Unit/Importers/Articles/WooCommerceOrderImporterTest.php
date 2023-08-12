<?php

namespace Tests\Unit\Importers\Articles;

use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use Tests\Support\Snapshots\JsonSnapshot;
use App\Importers\Articles\WooCommerceOrderImporter;

class WooCommerceOrderImporterTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_update_or_create_an_order_from_woocommerce_api()
    {
        $woocommerce_order_id = 619687;
        $woocommerce_order_response = JsonSnapshot::get('tests/snapshots/woocommerce/orders/' . $woocommerce_order_id . '.json', function () use ($woocommerce_order_id) {
            return (new \App\APIs\WooCommerce\WooCommerce())->order($woocommerce_order_id);
        });
        $woocommerce_order = $woocommerce_order_response['data'];

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
            $this->assertEquals(round($line_item['total'] * (1 + 0.15), 6), $article->unit_cost);
            $this->assertEquals(round($line_item['total'] * (1 + 0.15) * 3, 6), $article->unit_price);
            $this->assertEquals('NM', $article->condition);
            $this->assertEquals(false, $article->is_foil);
            $this->assertEquals(\App\Models\Localizations\Language::DEFAULT_ID, $article->language_id);

            $source_sort++;
        }

        WooCommerceOrderImporter::import($this->user->id, $woocommerce_order);

        $this->assertCount($quantity, Article::all());
        $this->assertCount($quantity, $storage_order->articles);
    }

    /**
     * @test
     */
    public function it_can_update_or_create_an_article_from_woocommerce_api_line_item()
    {
        $line_item = json_decode('{"id":180779,"name":"Force of Negation (V.1) - Foil","product_id":609530,"variation_id":0,"quantity":2,"tax_class":"","subtotal":"46.50","subtotal_tax":"0.00","total":"100.00","total_tax":"0.00","taxes":[],"meta_data":[{"id":1499559,"key":"zustand","value":"Near Mint (NM)","display_key":"Kartenzustand","display_value":"Near Mint"},{"id":1499560,"key":"sprache","value":"Englisch","display_key":"Sprache","display_value":"Englisch"},{"id":1499561,"key":"foil","value":"Ja","display_key":"Foil","display_value":"Ja"}],"sku":"265882-true","price":46.5,"image":{"id":"609529","src":"https:\/\/sammelkartenankauf.de\/wp-content\/uploads\/5396b405-6fa0-43d7-a8f6-f64154e95e98.jpg"},"parent_name":null}', true);
        $woocommerce_order = [
            'id' => 619687,
            'line_items' => [
                $line_item
            ],
            'payment_method' => 'cod',
        ];


        $card = factory(Card::class)->create([
            'game_id' => Game::ID_MAGIC,
            'cardmarket_product_id' => 265882,
        ]);

        $storage_woocommerce = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'WooCommerce',
        ]);

        $storage_order = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'Bestellung #' . $woocommerce_order['id'],
            'parent_id' => $storage_woocommerce->id,
        ]);

        $WooCommerceOrderImporter = new WooCommerceOrderImporter($this->user->id);
        $WooCommerceOrderImporter->setBonus($woocommerce_order);
        $WooCommerceOrderImporter->setAdditionalUnitCost($woocommerce_order);
        $WooCommerceOrderImporter->setStorage($woocommerce_order);

        $WooCommerceOrderImporter->importLineItem($line_item);

        $articles = Article::where('source_slug', WooCommerceOrderImporter::SOURCE_SLUG)
            ->where('source_id', $line_item['id'])
            ->get();

        $this->assertCount($line_item['quantity'], $articles);
        $this->assertCount($line_item['quantity'], Article::all());
        $this->assertEquals($this->user->id, $articles[0]->user_id);
        $this->assertEquals($card->id, $articles[0]->card_id);
        $this->assertEquals('woocommerce-api', $articles[0]->source_slug);
        $this->assertEquals($line_item['id'], $articles[0]->source_id);
        $this->assertEquals(\App\Models\Localizations\Language::DEFAULT_ID, $articles[0]->language_id);
        $this->assertEquals('NM', $articles[0]->condition);
        $this->assertEquals(57.5, $articles[0]->unit_cost);
        $this->assertEquals(172.5, $articles[0]->unit_price);
        $this->assertEquals($storage_order->id, $articles[0]->storage_id);
        $this->assertEquals(1, $articles[0]->source_sort);
        $this->assertEquals(2, $articles[1]->source_sort);
        $this->assertNull($articles[0]->number);

        $WooCommerceOrderImporter->importLineItem($line_item);

        $this->assertCount($line_item['quantity'], Article::all());
    }
}