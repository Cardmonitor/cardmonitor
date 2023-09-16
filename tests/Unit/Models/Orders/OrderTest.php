<?php

namespace Tests\Unit\Models\Orders;

use Mockery;
use Tests\TestCase;
use App\Models\Items\Item;
use Illuminate\Support\Arr;
use App\Models\Images\Image;
use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Models\Orders\Evaluation;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use App\Models\Users\CardmarketUser;
use App\Models\Localizations\Language;
use Tests\Traits\RelationshipAssertions;
use Tests\Support\Snapshots\JsonSnapshot;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class OrderTest extends TestCase
{
    use RelationshipAssertions;

    /**
     * @test
     */
    public function it_knows_its_routes()
    {
        $model = factory(Order::class)->create();
        $this->assertEquals(config('app.url') . '/order/' . $model->id, $model->path);
        $this->assertEquals(config('app.url') . '/order/' . $model->id . '/edit', $model->editPath);
    }

    /**
     * @test
     */
    public function it_knows_if_it_can_have_images()
    {
        $buyer = factory(CardmarketUser::class)->create([
            'cardmarket_user_id' => 1,
        ]);

        $seller = factory(CardmarketUser::class)->create([
            'cardmarket_user_id' => 2,
        ]);

        $model = factory(Order::class)->create([
            'received_at' => null,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
        $this->assertTrue($model->canHaveImages());

        $model = factory(Order::class)->create([
            'received_at' => now(),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
        $this->assertTrue($model->canHaveImages());

        $model = factory(Order::class)->create([
            'received_at' => now()->subDays(Order::DAYS_TO_HAVE_IAMGES),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
        $this->assertTrue($model->canHaveImages());

        $model = factory(Order::class)->create([
            'received_at' => now()->subDays((Order::DAYS_TO_HAVE_IAMGES + 1)),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);
        $this->assertFalse($model->canHaveImages());
    }

    /**
     * @test
     */
    public function it_has_one_evaluation()
    {
        $model = factory(Order::class)->create();
        $related = factory(Evaluation::class)->create([
            'order_id' => $model->id,
        ]);

        $this->assertHasOne($model, $related, 'evaluation');
    }

    /**
     * @test
     */
    public function it_belongs_to_many_articles()
    {
        $model = factory(Order::class)->create();
        $related = factory(Article::class)->create();

        $this->assertEquals(BelongsToMany::class, get_class($model->articles()));
    }

    /**
     * @test
     */
    public function it_belongs_to_a_buyer()
    {
        $cardmarketUser = factory(CardmarketUser::class)->create();

        $model = factory(Order::class)->create([
            'buyer_id' => $cardmarketUser->id
        ]);

        $this->assertBelongsTo($model, $cardmarketUser, 'buyer');
    }

    /**
     * @test
     */
    public function it_belongs_to_a_seller()
    {
        $cardmarketUser = factory(CardmarketUser::class)->create();

        $model = factory(Order::class)->create([
            'seller_id' => $cardmarketUser->id
        ]);

        $this->assertBelongsTo($model, $cardmarketUser, 'seller');
    }

    /**
     * @test
     */
    public function it_has_many_images()
    {
        $model = factory(Order::class)->create();

        $this->assertMorphMany($model, Image::class, 'images');
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_be_created_from_cardmarket()
    {
        $cardmarketOrder = json_decode(file_get_contents('tests/snapshots/cardmarket/order/get_seller_paid.json'), true);

        $cardmarket_product_mock = Mockery::mock('overload:' . \Cardmonitor\Cardmarket\Product::class);

        $cards = [];
        $expansions = [];
        foreach ($cardmarketOrder['order']['article'] as $key => $cardmarketArticle) {
            $cardmarket_product_id = $cardmarketArticle['idProduct'];

            $cardmarket_product_response = JsonSnapshot::get('tests/snapshots/cardmarket/product/' . $cardmarket_product_id . '.json', function () use ($cardmarket_product_id) {
                return (App::make('CardmarketApi'))->product->get($cardmarket_product_id);
            });

            $cardmarket_product_mock->shouldReceive('get')
                ->with($cardmarket_product_id)
                ->andReturn($cardmarket_product_response);

            $cardmarket_product = $cardmarket_product_response['product'];

            $expansion_id = $cardmarket_product['expansion']['idExpansion'];
            if (! Arr::has($expansions, $expansion_id)) {
                $expansions[$expansion_id] = factory(Expansion::class)->create([
                    'id' => $expansion_id,
                    'cardmarket_expansion_id' => $cardmarket_product['expansion']['idExpansion'],
                    'name' => $cardmarket_product['expansion']['enName'],
                ]);
            }

            $cards[$key] = factory(\App\Models\Cards\Card::class)->create([
                'cardmarket_product_id' => $cardmarket_product_id,
                'rarity' => $cardmarketArticle['product']['rarity'],
                'name' => $cardmarketArticle['product']['enName'],
                'expansion_id' => $expansions[$expansion_id]->id,
            ]);
        }

        $number_from_cardmarket_comments = Article::numberFromCardmarketComments($cardmarketOrder['order']['article'][2]['comments']);
        $article_with_number = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $cards[2]->id,
            'cardmarket_article_id' => $cardmarketOrder['order']['article'][2]['idArticle'],
            'number' => $number_from_cardmarket_comments,
            'language_id' => $cardmarketOrder['order']['article'][2]['language']['idLanguage'],
            'condition' => $cardmarketOrder['order']['article'][2]['condition'],
            'unit_price' => $cardmarketOrder['order']['article'][2]['price'],
            'is_foil' => $cardmarketOrder['order']['article'][2]['isFoil'],
            'is_signed' => $cardmarketOrder['order']['article'][2]['isSigned'],
            'is_altered' => $cardmarketOrder['order']['article'][2]['isAltered'],
            'is_playset' => $cardmarketOrder['order']['article'][2]['isPlayset'],
            'is_sellable_since' => now(),
        ]);

        $articlesWithCardmarketArticleId = factory(Article::class, 2)->create([
            'user_id' => $this->user->id,
            'card_id' => $cards[1]->id,
            'cardmarket_article_id' => $cardmarketOrder['order']['article'][1]['idArticle'],
            'language_id' => $cardmarketOrder['order']['article'][1]['language']['idLanguage'],
            'condition' => $cardmarketOrder['order']['article'][1]['condition'],
            'unit_price' => $cardmarketOrder['order']['article'][1]['price'],
            'is_foil' => $cardmarketOrder['order']['article'][1]['isFoil'],
            'is_signed' => $cardmarketOrder['order']['article'][1]['isSigned'],
            'is_altered' => $cardmarketOrder['order']['article'][1]['isAltered'],
            'is_playset' => $cardmarketOrder['order']['article'][1]['isPlayset'],
            'is_sellable_since' => now(),
        ]);

        $order = Order::updateOrCreateFromCardmarket($this->user->id, $cardmarketOrder['order']);

        $this->assertCount(1, Order::all());
        $this->assertCount(2, CardmarketUser::all());
        $this->assertCount(4, \App\Models\Cards\Card::all());
        $this->assertCount($cardmarketOrder['order']['articleCount'], $order->fresh()->articles);

        foreach ($order->articles as $article) {
            $this->assertNotNull($article->first()->sold_at);
            $this->assertEquals(1, $article->first()->is_sold);
            $this->assertNotNull($article->first()->is_sellable_since);
            $this->assertEquals(0, $article->first()->is_sellable);
        }

        $articlesNotinOrder = factory(Article::class, 2)->create([
            'user_id' => $this->user->id,
            'card_id' => $cards[1]->id,
            'cardmarket_article_id' => $cardmarketOrder['order']['article'][1]['idArticle'],
            'language_id' => $cardmarketOrder['order']['article'][1]['language']['idLanguage'],
            'condition' => $cardmarketOrder['order']['article'][1]['condition'],
            'unit_price' => $cardmarketOrder['order']['article'][1]['price'],
            'is_foil' => !$cardmarketOrder['order']['article'][1]['isFoil'],
            'is_signed' => $cardmarketOrder['order']['article'][1]['isSigned'],
            'is_altered' => $cardmarketOrder['order']['article'][1]['isAltered'],
            'is_playset' => $cardmarketOrder['order']['article'][1]['isPlayset'],
            'is_sellable_since' => now(),
        ]);

        $order->articles()->syncWithoutDetaching($articlesNotinOrder->pluck('id')->toArray());

        $order = Order::updateOrCreateFromCardmarket($this->user->id, $cardmarketOrder['order'], true);

        $this->assertCount(1, Order::all());
        $this->assertCount($cardmarketOrder['order']['articleCount'], $order->fresh()->articles);
        $this->assertCount(2, CardmarketUser::all());

        foreach ($order->articles as $article) {
            $this->assertNotNull($article->first()->sold_at);
            $this->assertEquals(1, $article->first()->is_sold);
            $this->assertNotNull($article->first()->is_sellable_since);
            $this->assertEquals(0, $article->first()->is_sellable);
        }
    }

    /**
     * @test
     */
    public function it_finds_its_items()
    {
        $model = factory(Order::class)->create([
            'articles_count' => 25,
        ]);

        $item = factory(Item::class)->create([
            'user_id' => $model->user_id,
        ]);
        $item->quantities()->create([
            'effective_from' => '1970-00-00 02:00:00',
            'end' => 50,
            'quantity' => 1,
            'start' => 1,
            'user_id' => $model->user_id,
        ]);
        $item->quantities()->create([
            'effective_from' => '1970-00-00 02:00:00',
            'end' => 9999,
            'quantity' => 2,
            'start' => 51,
            'user_id' => $model->user_id,
        ]);

        $model->findItems();

        $this->assertCount(1, $model->fresh()->sales()->where('item_id', $item->id)->get());

        $this->assertEquals(1, $model->sales()->where('item_id', $item->id)->first()->quantity);

    }

    /**
     * @test
     */
    public function it_knows_if_it_is_presale()
    {
        $expansion = factory(Expansion::class)->create([
            'released_at' => null,
        ]);

        $card = factory(\App\Models\Cards\Card::class)->create([
            'expansion_id' => $expansion->id,
        ]);

        $model = factory(Order::class)->create([

        ]);

        $article = factory(Article::class)->create([
            'user_id' => $model->user_id,
            'card_id' => $card->id,
        ]);

        $model->articles()->attach($article->id);

        $this->assertCount(1, $model->articles);
        $this->assertTrue($model->isPresale());

        $expansion->update([
            'released_at' => now()->addDays(2),
        ]);
        $this->assertTrue($model->fresh()->isPresale());

        $expansion->update([
            'released_at' => now()->addDay(),
        ]);
        $this->assertFalse($model->fresh()->isPresale());
    }

    /**
     * @test
     */
    public function it_can_be_filtered_by_state_cancelled()
    {
        $orders = [];
        $orders_count = 0;
        foreach (Order::STATES as $state => $label) {
            $orders[$state] = factory(Order::class)->create([
                'user_id' => $this->user->id,
                'state' => $state,
            ]);
            $orders_count++;
        }

        $cancelled_order = $orders[Order::STATE_CANCELLED];

        Arr::forget($orders, Order::STATE_CANCELLED);
        $not_canceled_order_count = count($orders);

        $not_cancelled_order_ids = Arr::pluck($orders, 'id');
        sort($not_cancelled_order_ids);

        $cancelled_orders = Order::cancelled(1)->get();
        $this->assertCount(1, $cancelled_orders);
        $this->assertEquals($cancelled_order->id, $cancelled_orders->first()->id);

        $not_cancelled_orders = Order::cancelled(0)->get();
        $this->assertCount($not_canceled_order_count, $not_cancelled_orders);
        $this->assertEquals($not_cancelled_order_ids, $not_cancelled_orders->pluck('id')->toArray());

        $this->assertCount($orders_count, Order::cancelled(-1)->get());
        $this->assertCount($orders_count, Order::cancelled(null)->get());
        $this->assertCount($orders_count, Order::all());
    }

    /**
     * @test
     */
    public function it_can_only_be_imported_if_state_is_on_hold()
    {
        $order = factory(Order::class)->create([
            'state' => \App\APIs\WooCommerce\Status::ON_HOLD->value,
        ]);

        $this->assertTrue($order->is_importable);

        $order->update([
            'state' => \App\APIs\WooCommerce\Status::PROCESSING->value,
        ]);

        $this->assertFalse($order->fresh()->is_importable);
    }
}
