<?php

namespace Tests\Unit\Models\Articles;

use Mockery;
use App\User;
use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use App\Models\Rules\Rule;
use Illuminate\Support\Arr;
use App\Models\Orders\Order;
use App\Models\Articles\Article;
use App\Models\Storages\Storage;
use Cardmonitor\Cardmarket\Stock;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use Tests\Traits\AttributeAssertions;
use App\Models\Localizations\Language;
use Tests\Traits\RelationshipAssertions;
use Tests\Support\Snapshots\JsonSnapshot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ArticleTest extends TestCase
{
    use AttributeAssertions, RelationshipAssertions;

    /**
     * @test
     */
    public function it_sets_bought_at_from_formated_value()
    {
        $this->assertSetsFormattedDatetime(Article::class, 'bought_at');
    }

    /**
     * @test
     */
    public function it_sets_sold_at_from_formated_value()
    {
        $this->assertSetsFormattedDatetime(Article::class, 'sold_at');
    }

    /**
     * @test
     */
    public function it_sets_unit_cost_from_formated_value()
    {
        $this->assertSetsFormattedNumber(Article::class, 'unit_cost');
    }

    /**
     * @test
     */
    public function it_sets_unit_price_from_formated_value()
    {
        $this->assertSetsFormattedNumber(Article::class, 'unit_price');
    }

    /**
     * @test
     */
    public function it_sets_provision_from_formated_value()
    {
        $this->assertSetsFormattedNumber(Article::class, 'provision');
    }

    /**
     * @test
     */
    public function it_belongs_to_rule()
    {
        $rule = factory(Rule::class)->create();

        $model = factory(Article::class)->create([
            'rule_id' => $rule->id,
        ]);
        $this->assertEquals(BelongsTo::class, get_class($model->rule()));
    }

    /**
     * @test
     */
    public function it_belongs_to_a_card()
    {
        $model = factory(Article::class)->create([
            'language_id' => Language::DEFAULT_ID,
        ]);
        $this->assertEquals(BelongsTo::class, get_class($model->language()));
    }

    /**
     * @test
     */
    public function it_belongs_to_a_language()
    {
        $card = factory(Card::class)->create();

        $model = factory(Article::class)->create([
            'card_id' => $card->id,
            'language_id' => Language::DEFAULT_ID,
        ]);
        $this->assertEquals(BelongsTo::class, get_class($model->card()));
    }

    /**
     * @test
     */
    public function it_belongs_to_many_orders()
    {
        $parent = factory(Order::class)->create();
        $model = factory(Article::class)->create();
        $this->assertEquals(BelongsToMany::class, get_class($model->orders()));
    }

    /**
     * @test
     */
    public function it_belongs_to_an_user()
    {
        $model = factory(Article::class)->create([
            'user_id' => factory(User::class)->create()->id,
        ]);
        $this->assertEquals(BelongsTo::class, get_class($model->user()));
    }

    /**
     * @test
     */
    public function it_has_a_localized_name()
    {
        $card = factory(Card::class)->create();

        $model = factory(Article::class)->create([
            'card_id' => $card->id,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $this->assertEquals($card->localizations()->where('language_id', Language::DEFAULT_ID)->first()->name, $model->local_name);
    }

    /**
     * @test
     */
    public function it_can_be_reindexed()
    {
        $cardmarket_article_id = 1;
        $card = factory(Card::class)->create();

        factory(Article::class, 3)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
            'index' => 1,
            'card_id' => $card->id,
        ]);

        $affected = Article::reindex($cardmarket_article_id);
        $this->assertEquals(3, $affected);

        $collection = Article::where('cardmarket_article_id', $cardmarket_article_id)->orderBy('index', 'ASC')->get();

        $index = 1;
        foreach ($collection as $model) {
            $this->assertEquals($index, $model->index);
            $index++;
        };
    }

    /**
     * @test
     */
    public function it_calculates_its_provision()
    {
        $model = new Article();
        $model->unit_price = 1;
        $this->assertEquals(0.05, $model->provision);

        $model->unit_price = 0.02;
        $this->assertEquals(0.01, $model->provision);

        $model->unit_price_formatted = 0.44;
        $this->assertEquals(0.03, $model->provision);

        $model->unit_price_formatted = 1;
        $this->assertEquals(0.05, $model->provision);
    }

    /**
     * @test
     */
    public function it_can_be_transformed_to_cardmarket()
    {
        $model = factory(Article::class)->create();
        $cardmarketModel = $model->toCardmarket();

        $this->assertArrayHasKey('idLanguage', $cardmarketModel);
        $this->assertArrayHasKey('comments', $cardmarketModel);
        $this->assertArrayHasKey('count', $cardmarketModel);
        $this->assertArrayHasKey('price', $cardmarketModel);
        $this->assertArrayHasKey('condition', $cardmarketModel);
        $this->assertArrayHasKey('isFoil', $cardmarketModel);
        $this->assertArrayHasKey('isSigned', $cardmarketModel);
        $this->assertArrayHasKey('isPlayset', $cardmarketModel);
    }

    /**
     * @test
     */
    public function it_can_be_updated_or_created_from_cardmarket_order()
    {
        $cardmarketOrder = json_decode(file_get_contents('tests/snapshots/cardmarket/order/get_seller_paid.json'), true);
        $cardmarketArticle = $cardmarketOrder['order']['article'][0];

        factory(\App\Models\Cards\Card::class)->create([
            'cardmarket_product_id' => $cardmarketArticle['idProduct'],
            'rarity' => $cardmarketArticle['product']['rarity'],
        ]);
        $article = Article::updateOrCreateFromCardmarketOrder($this->user->id, $cardmarketArticle);

        $this->assertEquals($cardmarketArticle['idProduct'], $article->card_id);
        $this->assertEquals($cardmarketArticle['condition'], $article->condition);
        $this->assertEquals($cardmarketArticle['price'], $article->unit_price);
    }

    /**
     * @test
     */
    public function it_sets_rarity_sort()
    {
        $model = new Article([
            'condition' => 'NM',
        ]);

        $this->assertEquals(5, $model->condition_sort);
    }

    /**
     * @test
     */
    public function it_handles_invalid_condition()
    {
        $model = new Article([
            'condition' => 'Ungueltig',
        ]);

        $this->assertEquals(0, $model->condition_sort);
    }

    /**
     * @test
     */
    public function it_gets_articles_grouped_by_cardmarket_article_id()
    {
        $card = factory(Card::class)->create();

        factory(Article::class, 3)->create([
            'card_id' => $card->id,
            'cardmarket_article_id' => 123,
            'condition' => 'NM',
            'unit_price' => 1.230000,
            'user_id' => $this->user->id,
        ]);

        $articles = Article::stock()
            ->where('user_id', $this->user->id)
            ->get();

        $this->assertCount(1, $articles);
        $this->assertEquals($this->user->id, $articles->first()->user_id);
        $this->assertEquals('3', $articles->first()->amount);
    }

    /**
     * @test
     */
    public function it_gets_its_amount()
    {
        $model = factory(Article::class)->create();
        $model->refresh();

        $this->assertEquals(Article::count(), $model->amount);

        $model = $model->copy();

        $this->assertEquals(Article::count(), $model->amount);

        $model = $model->copy();

        $this->assertEquals(Article::count(), $model->amount);
    }

    /**
     * @test
     */
    public function it_can_be_copied()
    {
        $oldModel = factory(Article::class)->create();
        $oldModel->refresh();
        $newModel = $oldModel->copy();

        $this->assertCount(2, Article::all());

        $this->assertEquals($newModel->card_id, $oldModel->card_id);
        $this->assertEquals($newModel->cardmarket_article_id, $oldModel->cardmarket_article_id);
        $this->assertEquals($newModel->cardmarket_comments, $oldModel->cardmarket_comments);
        $this->assertEquals($newModel->condition, $oldModel->condition);
        $this->assertEquals($newModel->is_foil, $oldModel->is_foil);
        $this->assertEquals($newModel->is_playset, $oldModel->is_playset);
        $this->assertEquals($newModel->is_signed, $oldModel->is_signed);
        $this->assertEquals($newModel->language_id, $oldModel->language_id);
        $this->assertEquals($newModel->storage_id, $oldModel->storage_id);
        $this->assertEquals($newModel->unit_cost, $oldModel->unit_cost);
        $this->assertEquals($newModel->unit_price, $oldModel->unit_price);
        $this->assertEquals($newModel->user_id, $oldModel->user_id);
    }

    /**
     * @test
     */
    public function it_can_set_its_amount()
    {
        $model = factory(Article::class)->create();
        $model->refresh();

        $newAmount = 3;
        $affected = 2;

        $result = $model->setAmount($newAmount, $sync = false);
        $this->assertCount($newAmount, Article::all());

        $this->assertEquals($affected, $result['amount']);
        $this->assertEquals($affected, $result['affected']);

        $newAmount = 3;
        $affected = 0;

        $result = $model->setAmount($newAmount, $sync = false);
        $this->assertCount($newAmount, Article::all());

        $this->assertEquals($newAmount, $result['amount']);
        $this->assertEquals($affected, $result['affected']);

        $newAmount = 1;
        $affected = 2;
        $result = $model->setAmount($newAmount, $sync = false);
        $this->assertCount($newAmount, Article::all());

        $this->assertEquals($affected, $result['amount']);
        $this->assertEquals($affected, $result['affected']);
    }

    /**
     * @test
     */
    public function it_can_increment_its_amount()
    {
        $amount = 2;
        $model = factory(Article::class)->create();
        $model->refresh();

        $result = $model->incrementAmount($amount, $sync = false);

        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals($amount, $result['affected']);

        $articles = Article::all();

        $this->assertCount(3, $articles);
        $this->assertEquals(1, $articles->first()->index);
        $this->assertEquals(3, $articles->last()->index);
    }

    /**
     * @test
     */
    public function it_can_decrement_its_amount()
    {
        $model = factory(Article::class)->create();
        $model->refresh();

        $this->assertEquals(Article::count(), $model->amount);

        $model = $model->copy();

        $this->assertEquals(Article::count(), $model->amount);

        $model = $model->copy();

        $this->assertEquals(Article::count(), $model->amount);

        $amount = 2;
        $result = $model->decrementAmount($amount, $sync = false);

        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals($amount, $result['affected']);
        $this->assertEquals(1, Article::count());
        $this->assertEquals(1, Article::first()->index);
    }

    /**
     * @test
     */
    public function it_can_get_the_max_cardmarket_article_attribute()
    {
        $cardmarket_article_id = 1;

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
        ]);
        $model->refresh();

        $model = $model->copy();
        $model->update([
            'cardmarket_article_id' => null,
        ]);

        $this->assertEquals($cardmarket_article_id, $model->max_cardmarket_article_id);
    }

    /**
     * @test
     */
    public function it_can_set_the_cardmarket_article_id_for_similar_articles()
    {
        $cardmarket_article_id = 1;
        $different_cardmarket_article_id = 2;

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
        ]);
        $model->refresh();

        $model = $model->copy();
        $model->update([
            'cardmarket_article_id' => null,
        ]);

        $differentModel = factory(Article::class)->create([
            'user_id' => $model->user_id,
            'cardmarket_article_id' => $different_cardmarket_article_id,
        ]);
        $model->refresh();

        $model->setCardmarketArticleIdForSimilar();

        $this->assertEquals($cardmarket_article_id, $model->fresh()->cardmarket_article_id);
        $this->assertCount(2, Article::where('cardmarket_article_id', $cardmarket_article_id)->get());
    }

    /**
     * @test
     */
    public function it_can_sync_the_amount_from_cardmarket_lt()
    {
        $returnValue = json_decode(file_get_contents('tests/snapshots/cardmarket/stock/article.json'), true);
        $cardmarket_article_id = $returnValue['article']['idArticle'];
        $cardmarket_articles_count = $returnValue['article']['count'];

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('article')
            ->with($cardmarket_article_id)
            ->andReturn($returnValue);

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
        ]);
        $model->refresh();

        $model->syncAmount();

        $articles = Article::where('cardmarket_article_id', $cardmarket_article_id)->get();

        $this->assertCount($cardmarket_articles_count, $articles);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_can_sync_the_amount_from_cardmarket_gt()
    {
        $returnValue = json_decode(file_get_contents('tests/snapshots/cardmarket/stock/article.json'), true);
        $cardmarket_article_id = $returnValue['article']['idArticle'];
        $cardmarket_articles_count = $returnValue['article']['count'];

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('article')
            ->with($cardmarket_article_id)
            ->andReturn($returnValue);

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
        ]);
        $model->refresh();
        $model->copy();
        $model->copy();

        $model->syncAmount();

        $articles = Article::where('cardmarket_article_id', $cardmarket_article_id)->get();

        $this->assertCount($cardmarket_articles_count, $articles);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_can_sync_the_amount_from_cardmarket_eq()
    {
        $returnValue = json_decode(file_get_contents('tests/snapshots/cardmarket/stock/article.json'), true);
        $cardmarket_article_id = $returnValue['article']['idArticle'];
        $cardmarket_articles_count = $returnValue['article']['count'];

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('article')
            ->with($cardmarket_article_id)
            ->andReturn($returnValue);

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
        ]);
        $model->refresh();
        $model->copy();

        $model->syncAmount();

        $articles = Article::where('cardmarket_article_id', $cardmarket_article_id)->get();

        $this->assertCount($cardmarket_articles_count, $articles);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_can_sync_the_amount_from_cardmarket_invalid_cardmarket_article_id()
    {
        $this->markTestIncomplete('No way to search for similar articles on Cardmarket');

        $returnValue = json_decode(file_get_contents('tests/snapshots/cardmarket/stock/article.json'), true);
        $cardmarket_article_id = 1;
        $cardmarket_articles_count = $returnValue['article']['count'];

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('article')
            ->with($cardmarket_article_id)
            ->andReturn(null);

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
        ]);
        $model->refresh();

        $model->syncAmount();

        $articles = Article::where('cardmarket_article_id', $cardmarket_article_id)->get();

        $this->assertCount($cardmarket_articles_count, $articles);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_gets_the_attributes_from_the_sku()
    {
        $this->markTestSkipped('Wird aktuell nicht mehr gebraucht, weil es nur noch eine SKU gibt');

        $model = factory(Article::class)->create([
            'is_foil' => false,
            'is_altered' => false,
        ]);
        $model->refresh();

        $attributes = Article::skuToAttributes($model->sku);

        $this->assertEquals($model->card_id, $attributes['card_id']);
        $this->assertEquals($model->card->expansion->id, $attributes['expansion_id']);
        $this->assertEquals($model->language_id, $attributes['language_id']);
        $this->assertEquals($model->is_foil, (int) $attributes['is_foil']);
        $this->assertEquals($model->is_altered, (int) $attributes['is_altered']);

        $model = factory(Article::class)->create([
            'is_foil' => true,
            'is_altered' => true,
        ]);
        $model->refresh();

        $attributes = Article::skuToAttributes($model->sku);

        $this->assertEquals($model->card_id, $attributes['card_id']);
        $this->assertEquals($model->card->expansion->id, $attributes['expansion_id']);
        $this->assertEquals($model->language_id, $attributes['language_id']);
        $this->assertEquals($model->is_foil, (int) $attributes['is_foil']);
        $this->assertEquals($model->is_altered, (int) $attributes['is_altered']);

        $model = factory(Article::class)->create([
            'is_foil' => true,
            'is_altered' => false,
        ]);
        $model->refresh();

        $attributes = Article::skuToAttributes($model->sku);

        $this->assertEquals($model->card_id, $attributes['card_id']);
        $this->assertEquals($model->card->expansion->id, $attributes['expansion_id']);
        $this->assertEquals($model->language_id, $attributes['language_id']);
        $this->assertEquals($model->is_foil, (int) $attributes['is_foil']);
        $this->assertEquals($model->is_altered, (int) $attributes['is_altered']);

        $model = factory(Article::class)->create([
            'is_foil' => false,
            'is_altered' => true,
        ]);
        $model->refresh();

        $attributes = Article::skuToAttributes($model->sku);

        $this->assertEquals($model->card_id, $attributes['card_id']);
        $this->assertEquals($model->card->expansion->id, $attributes['expansion_id']);
        $this->assertEquals($model->language_id, $attributes['language_id']);
        $this->assertEquals($model->is_foil, (int) $attributes['is_foil']);
        $this->assertEquals($model->is_altered, (int) $attributes['is_altered']);
    }

    /**
     * @test
     */
    public function articles_can_be_synced_by_stock_file()
    {
        $path = 'tests/snapshots/cardmarket/articles/stock-1.csv';

        $game = factory(Game::class)->create([
            'id' => Game::ID_MAGIC,
            'name' => 'Magic the Gathering',
            'abbreviation' => 'MtG',
            'is_importable' => true,
        ]);

        $stockfile = fopen($path, "r");
        $article_count = 0;
        $cards = [];
        $expansions = [];
        $row_count = 0;
        while (($data = fgetcsv($stockfile, 2000, ";")) !== false) {

            if ($row_count == 0) {
                $row_count++;
                continue;
            }

            $article_count += $data[14];

            if (Arr::has($cards, $data[1])) {
                continue;
            }

            if (! Arr::has($expansions, $data[4])) {
                $expansion = factory(Expansion::class)->create([
                    'game_id' => $game->id,
                    'name' => $data[3],
                    'abbreviation' => $data[4],
                ]);
                $expansions[$data[4]] = $expansion;
            }
            else {
                $expansion = Arr::get($expansions, $data[4]);
            }

            $card = factory(Card::class)->create([
                'game_id' => $game->id,
                'expansion_id' => $expansion->id,
                'cardmarket_product_id' => $data[1],
                'name' => $data[2],
            ]);

            if ($data[7] != Language::DEFAULT_ID) {
                $card->localizations()->create([
                    'language_id' => $data[7],
                    'name' => $data[3],
                ]);
            }

            $cards[$data[1]] = $card;

            // Article with different cardmarket_article_id
            if ($row_count === 1) {
                $article_with_different_cardmarket_id = factory(Article::class)->create([
                    'user_id' => $this->user->id,
                    'card_id' => $data[1],
                    'language_id' => $data[7],
                    'cardmarket_article_id' => -1,
                    'condition' => $data[8],
                    'unit_price' => $data[6],
                    'sold_at' => null,
                    'is_in_shoppingcard' => false,
                    'is_foil' => ($data[9] == 'X' ? true : false),
                    'is_signed' => ($data[10] == 'X' ? true : false),
                    'is_altered' => ($data[11] == 'X' ? true : false),
                    'is_playset' => ($data[12] == 'X' ? true : false),
                    'cardmarket_comments' => $data[13],
                    'has_sync_error' => false,
                    'sync_error' => null,
                ]);
            }

            $row_count++;
        }

        $this->assertCount(1, Article::all());

        $cardmarket_article_ids = Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path);

        $this->assertCount($article_count, Article::all());

        $cardmarket_article_ids = Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path);

        $this->assertCount($article_count, Article::all());
    }

    /**
     * @test
     */
    public function articles_syncs_the_cards_if_the_prices_change()
    {
        $path_same_prices = 'tests/snapshots/cardmarket/articles/stock-same-prices.csv';
        $path_same_prices_again = 'tests/snapshots/cardmarket/articles/stock-same-prices-again.csv';
        $path_different_prices = 'tests/snapshots/cardmarket/articles/stock-different-prices.csv';

        $game = factory(Game::class)->create([
            'id' => Game::ID_MAGIC,
            'name' => 'Magic the Gathering',
            'abbreviation' => 'MtG',
            'is_importable' => true,
        ]);

        $expansion = factory(Expansion::class)->create([
            'game_id' => $game->id,
            'name' => 'Future Sight',
            'abbreviation' => 'FUT',
        ]);

        $card = factory(Card::class)->create([
            'game_id' => $game->id,
            'expansion_id' => $expansion->id,
            'cardmarket_product_id' => 15073,
            'name' => 'Bridge from Below',
        ]);

        $this->assertCount(0, Article::all());

        Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path_same_prices);

        $articles = Article::all();

        $this->assertCount(2, $articles);

        $article_1 = $articles->first();
        $article_2 = $articles->last();

        $this->assertDatabaseHas('articles', [
            'id' => $article_1->id,
            'unit_price' => 3.45,
            'cardmarket_article_id' => 361434871,
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article_2->id,
            'unit_price' => 3.45,
            'cardmarket_article_id' => 361434871,
        ]);

        Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path_different_prices);

        $this->assertCount(2, Article::all());
        $this->assertDatabaseHas('articles', [
            'id' => $article_1->id,
            'unit_price' => 3,
            'cardmarket_article_id' => 361434871,
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article_2->id,
            'unit_price' => 4,
            'cardmarket_article_id' => 361434959,
        ]);

        Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path_same_prices_again);

        $this->assertCount(2, Article::all());
        $this->assertDatabaseHas('articles', [
            'id' => $article_1->id,
            'unit_price' => 4,
            'cardmarket_article_id' => 361434959,
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article_2->id,
            'unit_price' => 4,
            'cardmarket_article_id' => 361434959,
        ]);
    }

    /**
     * @test
     */
    public function articles_syncs_the_cards_if_the_condition_changes()
    {
        $path_same_prices = 'tests/snapshots/cardmarket/articles/stock-same-prices.csv';
        $path_same_prices_again = 'tests/snapshots/cardmarket/articles/stock-same-prices-again.csv';
        $path_different_condition = 'tests/snapshots/cardmarket/articles/stock-different-condition.csv';

        $game = factory(Game::class)->create([
            'id' => Game::ID_MAGIC,
            'name' => 'Magic the Gathering',
            'abbreviation' => 'MtG',
            'is_importable' => true,
        ]);

        $expansion = factory(Expansion::class)->create([
            'game_id' => $game->id,
            'name' => 'Future Sight',
            'abbreviation' => 'FUT',
        ]);

        $card = factory(Card::class)->create([
            'game_id' => $game->id,
            'expansion_id' => $expansion->id,
            'cardmarket_product_id' => 15073,
            'name' => 'Bridge from Below',
        ]);

        $this->assertCount(0, Article::all());

        Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path_same_prices);

        $articles = Article::all();

        $this->assertCount(2, $articles);

        $article_1 = $articles->first();
        $article_2 = $articles->last();

        $this->assertDatabaseHas('articles', [
            'id' => $article_1->id,
            'unit_price' => 3.45,
            'cardmarket_article_id' => 361434871,
            'condition' => 'NM',
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article_2->id,
            'unit_price' => 3.45,
            'cardmarket_article_id' => 361434871,
            'condition' => 'NM',
        ]);

        Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path_different_condition);

        $this->assertCount(2, Article::all());
        $this->assertDatabaseHas('articles', [
            'id' => $article_1->id,
            'unit_price' => 3,
            'cardmarket_article_id' => 361434871,
            'condition' => 'NM',
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article_2->id,
            'unit_price' => 4,
            'cardmarket_article_id' => 361434959,
            'condition' => 'EX',
        ]);

        Article::syncFromStockFile($this->user->id, Game::ID_MAGIC, $path_same_prices_again);

        $this->assertCount(2, Article::all());
        $this->assertDatabaseHas('articles', [
            'id' => $article_1->id,
            'unit_price' => 4,
            'cardmarket_article_id' => 361434959,
            'condition' => 'NM',
        ]);
        $this->assertDatabaseHas('articles', [
            'id' => $article_2->id,
            'unit_price' => 4,
            'cardmarket_article_id' => 361434959,
            'condition' => 'NM',
        ]);
    }

    /**
     * @test
     */
    public function it_can_set_a_storage_and_a_slot()
    {
        $slot = 1;
        $slots = 100;
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'slots' => $slots,
        ]);

        $this->assertEquals($slots, $storage->slots);
        $this->assertEquals(0, $storage->articles()->count());

        $this->assertFalse($storage->isSlotAvailable(-1));
        $this->assertFalse($storage->isSlotAvailable($slot + $slots));

        $this->assertTrue($storage->isSlotAvailable($slot));

        $article->setStorage($storage, $slot)
            ->save();

        $this->assertFalse($storage->isSlotAvailable($slot));

        $this->assertEquals($storage->id, $article->storage_id);
        $this->assertEquals($slot, $article->slot);
        $this->assertEquals(1, $storage->articles()->count());

        $article->unsetStorage()
            ->save();

        $this->assertEquals(null, $article->storage_id);
        $this->assertEquals(0, $article->slot);
        $this->assertEquals(0, $storage->articles()->count());
    }

    /**
     * @test
     */
    public function it_can_genereate_the_next_number()
    {
        $this->assertEquals('A001.001', Article::incrementNumber());
        $this->assertEquals('A000.002', Article::incrementNumber('A000.001'));
        $this->assertEquals('A001.001', Article::incrementNumber('A000.850'));
        $this->assertEquals('B000.001', Article::incrementNumber('A999.850'));
        $this->assertEquals('B001.001', Article::incrementNumber('B000.850'));
        $this->assertEquals('AA000.001', Article::incrementNumber('Z999.850'));
        $this->assertEquals('AA000.002', Article::incrementNumber('AA000.001'));
    }

    /**
     * @test
     */
    public function it_gets_the_highest_article_number()
    {
        $this->assertEquals('', Article::maxNumber($this->user->id));

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'number' => null,
        ]);

        $this->assertEquals('', Article::maxNumber($this->user->id));

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'number' => 'A000.001',
        ]);

        $this->assertEquals('A000.001', Article::maxNumber($this->user->id));

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'number' => 'A99.999',
        ]);

        $this->assertEquals('A99.999', Article::maxNumber($this->user->id));
    }

    /**
     * @test
     */
    public function it_can_be_filtered_by_sold() {
        $article_sold = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'sold_at' => now(),
        ]);

        $article_not_sold = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'sold_at' => null,
        ]);

        $this->assertCount(2, Article::all());
        $this->assertCount(2, Article::sold(null)->get());
        $this->assertCount(2, Article::sold(-1)->get());

        $sold_articles = Article::sold(1)->get();
        $this->assertCount(1, $sold_articles);
        $this->assertEquals($article_sold->id, $sold_articles->first()->id);
        $this->assertEquals($article_sold->sold_at, $sold_articles->first()->sold_at);

        $not_sold_articles = Article::sold(0)->get();
        $this->assertCount(1, $not_sold_articles);
        $this->assertEquals($article_not_sold->id, $not_sold_articles->first()->id);
        $this->assertEquals($article_not_sold->sold_at, $not_sold_articles->first()->sold_at);
    }

    /**
     * @test
     */
    public function it_can_be_filtered_by_product_type() {

        $card_with_expansion = factory(Card::class)->create([

        ]);

        $card_without_expansion = factory(Card::class)->create([
            'expansion_id' => null,
        ]);

        $article_with_expansion = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card_with_expansion->id,
        ]);

        $article_without_expansion = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card_without_expansion->id,
        ]);

        $this->assertCount(2, Article::all());
        $this->assertCount(2, Article::productType(null)->get());
        $this->assertCount(2, Article::productType(-1)->get());

        $articles_with_expansion = Article::select('articles.*')->join('cards', 'cards.id', 'articles.card_id')->productType(1)->get();
        $this->assertCount(1, $articles_with_expansion);
        $this->assertEquals($article_with_expansion->id, $articles_with_expansion->first()->id);

        $articles_without_expansion = Article::select('articles.*')->join('cards', 'cards.id', 'articles.card_id')->productType(0)->get();
        $this->assertCount(1, $articles_without_expansion);
        $this->assertEquals($article_without_expansion->id, $articles_without_expansion->first()->id);
    }

    /**
     * @test
     */
    public function it_sets_the_is_sold_attribute_when_sold_at_is_set() {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'sold_at' => null,
            'is_sellable_since' => now(),
        ]);

        $now = now();

        $this->assertFalse($article->is_sold);

        $article->sold_at = $now;
        $article->save();

        $this->assertTrue($article->is_sold);
        $this->assertFalse($article->is_sellable);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $article->sold_at->format('Y-m-d H:i:s'));

        $article->sold_at = null;
        $article->save();

        $this->assertNull($article->sold_at);
        $this->assertFalse($article->is_sold);
    }

    /**
     * @test
     */
    public function it_sets_the_is_sellable_attribute_when_is_sold_since_is_set() {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'is_sellable_since' => null,
        ]);

        $now = now();

        $this->assertFalse($article->is_sellable);

        $article->is_sellable_since = $now;
        $article->save();

        $this->assertTrue($article->is_sellable);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $article->is_sellable_since->format('Y-m-d H:i:s'));

        $article->is_sellable_since = null;
        $article->save();

        $this->assertNull($article->is_sellable_since);
        $this->assertFalse($article->is_sellable);
    }

    /**
     * @test
     */
    public function it_knows_its_order_export_name()
    {
        $card = factory(Card::class)->create([
            'name' => 'Test Card',
        ]);

        $card->localizations()->create([
            'language_id' => 3,
            'name' => 'Test Karte',
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card->id,
            'condition' => 'EX',
            'is_foil' => false,
            'language_id' => 1,
        ]);

        $this->assertEquals('Test Card - EX - English', $article->order_export_name);

        $article->update([
            'language_id' => 3,
        ]);

        $this->assertEquals('Test Karte - EX - German', $article->fresh()->order_export_name);

        $article->update([
            'is_foil' => true,
        ]);

        $this->assertEquals('Test Karte - EX - German - Foil', $article->fresh()->order_export_name);
    }

    /**
     * @test
     */
    public function it_gets_the_number_from_its_cardmarket_comments()
    {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'cardmarket_comments' => null,
        ]);

        $this->assertEquals('', Article::numberFromCardmarketComments($article->cardmarket_comments));
        $this->assertEquals('', $article->numberFromCardmarketComments);

        $article->update([
            'cardmarket_comments' => 'Test',
        ]);

        $this->assertEquals('', Article::numberFromCardmarketComments($article->cardmarket_comments));
        $this->assertEquals('', $article->numberFromCardmarketComments);

        $article->update([
            'cardmarket_comments' => 'Test ##A000.001##',
        ]);

        $this->assertEquals('A000.001', Article::numberFromCardmarketComments($article->cardmarket_comments));
        $this->assertEquals('A000.001', $article->numberFromCardmarketComments);

        $article->update([
            'cardmarket_comments' => 'Test ##A000.001## Test',
        ]);

        $this->assertEquals('A000.001', Article::numberFromCardmarketComments($article->cardmarket_comments));
        $this->assertEquals('A000.001', $article->numberFromCardmarketComments);
    }

    /**
     * @test
     */
    public function it_sets_its_number_in_cardmarket_comments()
    {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'cardmarket_comments' => null,
        ]);

        $article->cardmarket_comments = null;

        $article->number = 'A000.001';
        $this->assertEquals('##A000.001##', $article->cardmarket_comments);

        $article->number = null;
        $this->assertSame(null, $article->cardmarket_comments);

        $article->cardmarket_comments = 'Test';
        $article->number = 'A000.001';
        $this->assertEquals('Test ##A000.001##', $article->cardmarket_comments);

        $article->number = 'A000.002';
        $this->assertEquals('Test ##A000.002##', $article->cardmarket_comments);

        $article->number = null;
        $this->assertEquals('Test', $article->cardmarket_comments);

        $article->cardmarket_comments = 'Test ##A000.001## Test';
        $article->number = 'A000.002';
        $this->assertEquals('Test ##A000.002## Test', $article->cardmarket_comments);

        $article->number = null;
        $this->assertEquals('Test Test', $article->cardmarket_comments);
    }

    /**
     * @test
     */
    public function it_resets_the_article_if_not_updated_on_cardmarket()
    {
        $cardmarket_update_response = json_decode(file_get_contents('tests/snapshots/cardmarket/articles/responses/update/failed.json'), true);
        $cardmarket_article_id = $cardmarket_update_response['notUpdatedArticles']['tried']['idArticle'];

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('update')
            ->andReturn($cardmarket_update_response);

        $cardmarket_article_response = json_decode(file_get_contents('tests/snapshots/cardmarket/articles/responses/article/pcg.json'), true);
        $cardmarket_article = $cardmarket_article_response['article'];

        $stockMock->shouldReceive('article')
            ->with($cardmarket_article_id)
            ->andReturn($cardmarket_article_response);

        $model = factory(Article::class)->create([
            'cardmarket_article_id' => $cardmarket_article_id,
            'user_id' => $this->user->id,
            'unit_price' => 1.23,
            'condition' => 'EX',
            'cardmarket_comments' => 'Comment',
            'is_foil' => false,
            'is_signed' => false,
            'is_playset' => false,
            'is_first_edition' => false,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $is_synced = $model->syncUpdate();

        $model->refresh();

        $this->assertFalse($is_synced);
        $this->assertEquals(false, $model->has_sync_error);
        $this->assertEquals($cardmarket_article['idArticle'], $model->cardmarket_article_id);
        $this->assertEquals($cardmarket_article['comments'], $model->cardmarket_comments);
        $this->assertEquals($cardmarket_article['price'], $model->unit_price);
        $this->assertEquals($cardmarket_article['condition'], $model->condition);
        $this->assertEquals(0, $model->is_foil);
        $this->assertEquals(1, $model->is_reverse_holo);
        $this->assertEquals(0, $model->is_signed);
        $this->assertEquals(0, $model->is_first_edition);
        $this->assertEquals(0, $model->is_playset);
        $this->assertEquals(0, $model->is_altered);
        $this->assertEquals($cardmarket_article['language']['idLanguage'], $model->language_id);
        $this->assertEquals($cardmarket_article['lastEdited'], $model->cardmarket_last_edited->format('Y-m-d\TH:i:sO'));

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_can_be_filtered_by_sync_state()
    {
        $now = now();
        $article_sync_state_success_1 = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'has_sync_error' => Article::SYNC_STATE_SUCCESS,
            'exported_at' => $now,
        ]);

        $article_sync_state_success_2 = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'has_sync_error' => Article::SYNC_STATE_SUCCESS,
            'synced_at' => $now,
        ]);

        $article_sync_state_success_3 = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'has_sync_error' => Article::SYNC_STATE_SUCCESS,
            'exported_at' => $now,
            'synced_at' => $now,
        ]);

        $article_sync_state_error = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'has_sync_error' => Article::SYNC_STATE_ERROR,
            'exported_at' => $now,
        ]);

        $article_sync_state_not_synced = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'has_sync_error' => Article::SYNC_STATE_SUCCESS,
            'exported_at' => null,
            'synced_at' => null,
        ]);

        $this->assertCount(5, Article::all());
        $this->assertCount(5, Article::query()->sync(null)->get());
        $this->assertCount(5, Article::query()->sync(-1)->get());

        $articles_sync_success = Article::query()->sync(Article::SYNC_STATE_SUCCESS)->get();
        $this->assertCount(3, $articles_sync_success);
        $this->assertEquals([
            $article_sync_state_success_1->id,
            $article_sync_state_success_2->id,
            $article_sync_state_success_3->id,
        ], $articles_sync_success->pluck('id')->toArray());

        $articles_sync_error = Article::query()->sync(Article::SYNC_STATE_ERROR)->get();
        $this->assertCount(1, $articles_sync_error);
        $this->assertEquals([
            $article_sync_state_error->id,
        ], $articles_sync_error->pluck('id')->toArray());

        $articles_not_synced = Article::query()->sync(Article::SYNC_STATE_NOT_SYNCED)->get();
        $this->assertCount(1, $articles_not_synced);
        $this->assertEquals([
            $article_sync_state_not_synced->id,
        ], $articles_not_synced->pluck('id')->toArray());
    }

    /**
     * @test
     */
    public function it_knows_its_local_and_card_names()
    {
        $name_en = 'Test Card';
        $name_de = 'Test Karte';
        $card = factory(Card::class)->create([
            'name' => $name_en,
        ]);

        $card->localizations()->create([
            'language_id' => Language::ID_GERMAN,
            'name' => $name_de,
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card->id,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $this->assertEquals($name_en, $article->local_name);
        $this->assertEquals($name_en, $article->card_name);

        $article->update([
            'language_id' => Language::ID_GERMAN,
        ]);

        $this->assertEquals($name_de, $article->local_name);
        $this->assertEquals($name_en, $article->card_name);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => null,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $this->assertEquals(null, $article->local_name);
        $this->assertEquals(null, $article->card_name);
    }

    /**
     * @test
     */
    public function it_can_be_searched_by_local_or_card_names()
    {
        $name_en = 'Test Card';
        $name_de = 'Test Karte';
        $card = factory(Card::class)->create([
            'name' => $name_en,
        ]);

        $card->localizations()->create([
            'language_id' => Language::ID_GERMAN,
            'name' => $name_de,
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card->id,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $this->assertEquals($name_en, $article->local_name);
        $this->assertEquals($name_en, $article->card_name);

        $this->assertCount(1, Article::search($name_en)->get());
        $this->assertCount(0, Article::search($name_de)->get());

        $article->update([
            'language_id' => Language::ID_GERMAN,
        ]);

        $this->assertEquals($name_de, $article->local_name);
        $this->assertEquals($name_en, $article->card_name);

        $this->assertCount(1, Article::search($name_en)->get());
        $this->assertCount(1, Article::search($name_de)->get());
    }

    /**
     * @test
     */
    public function it_can_create_articles_without_a_card()
    {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => null,
        ]);

        $this->assertNull($article->card);
        $this->assertNull($article->card_name);
        $this->assertNull($article->local_name);
    }

    /**
     * @test
     */
    public function it_has_many_external_ids()
    {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertCount(0, $article->externalIds);

        $external_id = $article->externalIds()->create([
            'user_id' => $this->user->id,
            'external_id' => 'test',
            'external_type' => 'test',
        ]);

        $this->assertCount(1, $article->externalIds()->get());
        $this->assertEquals($external_id->id, $article->externalIds()->first()->id);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_be_exported_to_cardmarket()
    {
        $sync_add_response = JsonSnapshot::get('tests/snapshots/cardmarket/articles/responses/add/success.json', function () {
            return [];
        });
        $cardmarket_product_id = $sync_add_response['inserted']['idArticle']['idProduct'];

        $card = factory(Card::class)->create([
            'cardmarket_product_id' => $cardmarket_product_id,
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card->id,
            'cardmarket_article_id' => null,
            'cardmarket_comments' => $sync_add_response['inserted']['idArticle']['comments'],
            'unit_price' => 1.23,
            'condition' => 'EX',
            'is_foil' => false,
            'is_signed' => false,
            'is_playset' => false,
            'is_first_edition' => false,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('add')
            ->andReturn($sync_add_response);

        $is_synced = $article->sync();

        $article->load('externalIds');
        $external_id = $article->externalIds()->first();

        $this->assertTrue($is_synced);
        $this->assertCount(1, $article->externalIds);
        $this->assertEquals($sync_add_response['inserted']['idArticle']['idArticle'], $article->cardmarket_article_id);
        $this->assertEquals($sync_add_response['inserted']['idArticle']['comments'], $article->cardmarket_comments);

        $this->assertEquals('cardmarket', $external_id->external_type);
        $this->assertEquals($sync_add_response['inserted']['idArticle']['idArticle'], $external_id->external_id);
        $this->assertEquals($sync_add_response['inserted']['idArticle']['lastEdited'], $external_id->external_updated_at->format('Y-m-d\TH:i:sO'));
        $this->assertEquals(Article::SYNC_STATE_SUCCESS, $external_id->sync_state);
        $this->assertNull($external_id->sync_message);

        Mockery::close();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_be_updated_on_cardmarket()
    {
        $sync_update_response = JsonSnapshot::get('tests/snapshots/cardmarket/articles/responses/update/success.json', function () {
            return [];
        });
        $cardmarket_product_id = $sync_update_response['updatedArticles']['idProduct'];

        $card = factory(Card::class)->create([
            'cardmarket_product_id' => $cardmarket_product_id,
        ]);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card->id,
            'cardmarket_article_id' => $sync_update_response['updatedArticles']['idArticle'],
            'cardmarket_comments' => $sync_update_response['updatedArticles']['comments'],
            'unit_price' => $sync_update_response['updatedArticles']['price'],
            'condition' => $sync_update_response['updatedArticles']['condition'],
            'is_foil' => false,
            'is_signed' => false,
            'is_playset' => false,
            'is_first_edition' => false,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $stockMock = Mockery::mock('overload:' . Stock::class);
        $stockMock->shouldReceive('update')
            ->andReturn($sync_update_response);

        $is_synced = $article->sync();

        $article->load('externalIds');
        $external_id = $article->externalIds()->first();

        $this->assertTrue($is_synced);
        $this->assertCount(1, $article->externalIds);
        $this->assertEquals($sync_update_response['updatedArticles']['idArticle'], $article->cardmarket_article_id);
        $this->assertEquals($sync_update_response['updatedArticles']['comments'], $article->cardmarket_comments);

        $this->assertEquals('cardmarket', $external_id->external_type);
        $this->assertEquals($sync_update_response['updatedArticles']['idArticle'], $external_id->external_id);
        $this->assertEquals($sync_update_response['updatedArticles']['lastEdited'], $external_id->external_updated_at->format('Y-m-d\TH:i:sO'));
        $this->assertEquals(Article::SYNC_STATE_SUCCESS, $external_id->sync_state);
        $this->assertNull($external_id->sync_message);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_can_be_transformed_to_woocommerce()
    {
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'number' => 'A000.001',
            'unit_price' => 1.23,
            'condition' => 'EX',
            'is_foil' => false,
            'is_signed' => false,
            'is_playset' => false,
            'is_first_edition' => false,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $woocommerce_product = $article->toWoocommerce();

        $this->assertEquals('A000.001', $woocommerce_product['sku']);
        $this->assertEquals(1.23, $woocommerce_product['regular_price']);
        $this->assertEquals([
            [
                'key' => 'cardmarket_comments',
                'value' => '##A000.001##',
            ],
            [
                'key' => 'local_name',
                'value' => $article->local_name,
            ],
            [
                'key' => 'game_id',
                'value' => $article->card->game_id,
            ],
            [
                'key' => 'condition',
                'value' => 'EX',
            ],
            [
                'key' => 'is_altered',
                'value' => 'Nein',
            ],
            [
                'key' => 'is_foil',
                'value' => 'Nein',
            ],
            [
                'key' => 'is_playset',
                'value' => 'Nein',
            ],
            [
                'key' => 'is_reverse_holo',
                'value' => 'Nein',
            ],
            [
                'key' => 'is_signed',
                'value' => 'Nein',
            ],
            [
                'key' => 'is_first_edition',
                'value' => 'Nein',
            ],
            [
                'key' => 'language_id',
                'value' => Language::DEFAULT_ID,
            ],
            [
                'key' => 'language_code',
                'value' => 'gb',
            ]
        ], $woocommerce_product['meta_data']);
    }

    /**
     * @test
     */
    public function it_can_be_exported_to_woocommerce()
    {
        $this->markTestSkipped('This test is skipped because it needs a valid WooCommerce API connection.');

        $cardmarket_product_id = 265882;
        $cardmarket_product_response = JsonSnapshot::get('tests/snapshots/cardmarket/product/' . $cardmarket_product_id . '.json', function () use ($cardmarket_product_id) {
            return App::make('CardmarketApi')->product->get($cardmarket_product_id);
        });

        $cardmarket_product = $cardmarket_product_response['product'];

        $expansion = factory(Expansion::class)->create([
            'id' => $cardmarket_product['expansion']['idExpansion'],
            'cardmarket_expansion_id' => $cardmarket_product['expansion']['idExpansion'],
            'name' => $cardmarket_product['expansion']['enName'],
        ]);

        $card = Card::createOrUpdateFromCardmarket($cardmarket_product, $expansion->id);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'card_id' => $card->id,
            'number' => 'A000.001',
            'unit_price' => 1.23,
            'condition' => 'EX',
            'is_foil' => false,
            'is_signed' => false,
            'is_playset' => false,
            'is_first_edition' => false,
            'language_id' => Language::DEFAULT_ID,
        ]);

        $article->syncWoocommerceAdd();
    }
}
