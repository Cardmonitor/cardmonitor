<?php

namespace Tests\Unit\Exporters\Orders;

use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Models\Localizations\Language;
use Illuminate\Support\Facades\Storage;
use App\Importers\Orders\ArticlesInOrdersCsvImporter;

class ArticlesInOrdersCsvImporterTest extends TestCase
{
    const ARTICLE_ROWS = [
        0 => [
            "Magic the Gathering Einzelkarten",
            "Product ID",
            "-",
            "2 Artikel",
            "Localized Article Name",
            "Expansion",
            "3.16",
            "Comments",
            "Condition",
            "Rarity",
            "Collector Number",
            "Language",
            "Order ID",
        ],
        1 => [
            "1",
            "290391",
            "1",
            "Night's Whisper",
            "Night's Whisper",
            "Eternal Masters",
            "1.98",
            "Fast and secure shipping | Check out our other Cards",
            "NM",
            "Common",
            "100",
            "Englisch",
            "1083560237",
        ],
        2 => [
            "1",
            "375039",
            "1",
            "Talisman of Conviction",
            "Talisman of Conviction",
            "Modern Horizons",
            "1.18",
            "Fast and secure shipping | Check out our other Cards",
            "NM",
            "Uncommon",
            "230",
            "Englisch",
            "1083560237",
        ],
    ];

    const FILEPATH = 'imports/articles_in_orders/articles_in_orders.csv';
    const FILEPATH_MULTIPLE_ORDERS = 'imports/articles_in_orders/articles_in_orders_2022-08-01.csv';

    protected ArticlesInOrdersCsvImporter $importer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importer = new ArticlesInOrdersCsvImporter();
    }

    /**
     * @test
     */
    public function it_can_create_a_card_if_it_does_not_exists()
    {
        $this->markTestSkipped();

        $article = self::ARTICLE_ROWS[1];

        $this->assertDatabaseMissing('cards', [
            'cardmarket_product_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
        ]);

        $card = $this->importer->ensureCardExists($article);

        $this->assertDatabaseHas('cards', [
            'id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
            'cardmarket_product_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
        ]);

        $this->assertInstanceOf(Card::class, $card);
        $this->assertEquals($article[ArticlesInOrdersCsvImporter::KEY_RARITY], $card->rarity);
        $this->assertEquals(2, $card->cmc);

        $this->assertDatabaseHas('cards', [
            'id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
            'cardmarket_product_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
        ]);

        $this->assertDatabaseHas('localizations', [
            'localizationable_type' => Card::class,
            'localizationable_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
            'language_id' => Language::DEFAULT_ID,
        ]);

        $this->assertCount(1, $card->localizations);
    }

    /**
     * @test
     */
    public function it_can_find_a_card_if_it_exists()
    {
        $this->markTestSkipped();

        $article = self::ARTICLE_ROWS[1];

        $card = factory(Card::class)->create([
            'cardmarket_product_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
        ]);

        $this->assertDatabaseHas('cards', [
            'id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
            'cardmarket_product_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
        ]);

        $card = $this->importer->ensureCardExists($article);

        $this->assertInstanceOf(Card::class, $card);
        $this->assertEquals($article[ArticlesInOrdersCsvImporter::KEY_RARITY], $card->rarity);
        $this->assertEquals(2, $card->cmc);

        $this->assertDatabaseHas('cards', [
            'id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
            'cardmarket_product_id' => $article[ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID],
        ]);
    }

    /**
     * @test
     */
    public function it_can_transform_the_rows_to_cardmarket_orders()
    {
        $this->markTestSkipped();

        $article_rows = self::ARTICLE_ROWS;

        $cardmarket_orders = $this->importer->toCardmarketOrders($article_rows);

        $this->assertCount(1, $cardmarket_orders['order']);

        $first_order = $cardmarket_orders['order'][array_key_first($cardmarket_orders['order'])];

        $this->assertEquals($article_rows[1][ArticlesInOrdersCsvImporter::KEY_ORDER_ID], $first_order['idOrder']);
        $this->assertEquals(2, $first_order['articleCount']);
        $this->assertCount(2, $first_order['article']);
        dump($first_order['article']);
        $this->assertEquals($article_rows[1][ArticlesInOrdersCsvImporter::KEY_CARDMARKET_PRODUCT_ID], $first_order['article'][0]['idProduct']);
    }

    /**
     * @test
     */
    public function it_can_create_an_order()
    {
        $this->markTestSkipped();

        $article_rows = self::ARTICLE_ROWS;
        $article_count = (count($article_rows) - 1);

        $this->importer->ensureCardsExists($article_rows);
        $cardmarket_orders = $this->importer->toCardmarketOrders($article_rows);

        $this->assertCount($this->user->id, $cardmarket_orders['order']);

        $first_order = $cardmarket_orders['order'][array_key_first($cardmarket_orders['order'])];

        $order_1 = $this->importer->createOrder($this->user->id, $first_order);
        $this->assertInstanceOf(Order::class, $order_1);
        $this->assertCount($article_count, $order_1->articles);
        $this->assertEquals($article_rows[0][ArticlesInOrdersCsvImporter::KEY_PRICE], $order_1->articles_revenue);

        $order_2 = $this->importer->createOrder($this->user->id, $first_order);
        $this->assertInstanceOf(Order::class, $order_2);
        $this->assertCount(1, Order::all());
        $this->assertEquals($order_1->id, $order_2->id);
        $this->assertCount($article_count, $order_1->fresh()->articles);
        $this->assertCount($article_count, $order_2->articles);
    }

    /**
     * @test
     */
    public function it_can_import_all_rows()
    {
        $this->markTestSkipped();

        $article_rows = self::ARTICLE_ROWS;
        $article_count = (count($article_rows) - 1);

        $orders = ArticlesInOrdersCsvImporter::import($this->user->id, $article_rows);
        $this->assertCount(1, $orders);
        $order_1 = $orders[array_key_first($orders)];

        $this->assertInstanceOf(Order::class, $order_1);
        $this->assertCount($article_count, $order_1->articles);
        $this->assertEquals($article_rows[0][ArticlesInOrdersCsvImporter::KEY_PRICE], $order_1->articles_revenue);

        $orders = ArticlesInOrdersCsvImporter::import($this->user->id, $article_rows);
        $this->assertCount(1, $orders);
        $order_2 = $orders[array_key_first($orders)];
        $this->assertInstanceOf(Order::class, $order_2);
        $this->assertCount(1, Order::all());
        $this->assertEquals($order_1->id, $order_2->id);
        $this->assertCount($article_count, $order_1->fresh()->articles);
        $this->assertCount($article_count, $order_2->articles);
    }

    /**
     * @test
     */
    public function it_can_create_article_rows_from_a_csv_file()
    {
        $this->markTestSkipped();

        $filepath = Storage::disk('local')->path(self::FILEPATH);
        $article_rows = ArticlesInOrdersCsvImporter::parseCsv($filepath);
        $this->assertEquals(self::ARTICLE_ROWS, $article_rows);
    }

    /**
     * @test
     */
    public function it_can_import_from_a_filepath()
    {
        $this->markTestSkipped();

        $article_rows = self::ARTICLE_ROWS;
        $article_count = (count($article_rows) - 1);

        $filepath = Storage::disk('local')->path(self::FILEPATH);
        $orders = ArticlesInOrdersCsvImporter::importFromFilepath($this->user->id, $filepath);
        $this->assertCount(1, $orders);
        $order_1 = $orders[array_key_first($orders)];

        $this->assertInstanceOf(Order::class, $order_1);
        $this->assertCount($article_count, $order_1->articles);
        $this->assertEquals($article_rows[0][ArticlesInOrdersCsvImporter::KEY_PRICE], $order_1->articles_revenue);

        $orders = ArticlesInOrdersCsvImporter::importFromFilepath($this->user->id, $filepath);
        $this->assertCount(1, $orders);
        $order_2 = $orders[array_key_first($orders)];
        $this->assertInstanceOf(Order::class, $order_2);
        $this->assertCount(1, Order::all());
        $this->assertEquals($order_1->id, $order_2->id);
        $this->assertCount($article_count, $order_1->fresh()->articles);
        $this->assertCount($article_count, $order_2->articles);
    }

    /**
     * @test
     */
    public function it_can_import_multiple_order_from_a_filepath()
    {
        $this->markTestSkipped();

        $articles_count = [
            3,
            1,
            1,
            1,
        ];

        $filepath = Storage::disk('local')->path(self::FILEPATH_MULTIPLE_ORDERS);
        $orders = ArticlesInOrdersCsvImporter::importFromFilepath($this->user->id, $filepath);
        $this->assertCount(4, $orders);
        $order_1 = $orders[array_key_first($orders)];

        $this->assertInstanceOf(Order::class, $order_1);

        foreach ($orders as $key => $order) {
            $this->assertCount($articles_count[$key], $order->articles);
        }

        $orders = ArticlesInOrdersCsvImporter::importFromFilepath($this->user->id, $filepath);
        $this->assertCount(4, $orders);
        $this->assertCount(4, Order::all());
        $order_2 = $orders[array_key_first($orders)];
        $this->assertInstanceOf(Order::class, $order_2);
        $this->assertEquals($order_1->id, $order_2->id);
        foreach ($orders as $key => $order) {
            $this->assertCount($articles_count[$key], $order->articles);
        }
    }


}
