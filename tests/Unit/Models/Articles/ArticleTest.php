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
use App\Models\Expansions\Expansion;
use Tests\Traits\AttributeAssertions;
use App\Models\Localizations\Language;
use Barryvdh\Debugbar\Twig\Extension\Dump;
use Tests\Traits\RelationshipAssertions;
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

        $this->assertEquals($card->localizations()->where('language_id', Language::DEFAULT_ID)->first()->name, $model->localName);
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
        $this->assertEquals('A000.001', Article::incrementNumber());
        $this->assertEquals('A000.002', Article::incrementNumber('A000.001'));
        $this->assertEquals('A001.001', Article::incrementNumber('A000.250'));
        $this->assertEquals('B000.001', Article::incrementNumber('A999.250'));
        $this->assertEquals('B001.001', Article::incrementNumber('B000.250'));
        $this->assertEquals('AA000.001', Article::incrementNumber('Z999.250'));
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
        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $article_in_cancelled_order = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $article_in_paid_order = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $cancelled_order = factory(Order::class)->create([
            'user_id' => $this->user->id,
            'state' => Order::STATE_CANCELLED,
        ]);

        $cancelled_order->articles()->attach($article_in_cancelled_order->id);

        $paid_order = factory(Order::class)->create([
            'user_id' => $this->user->id,
            'state' => Order::STATE_PAID,
        ]);

        $paid_order->articles()->attach($article_in_paid_order->id);

        $this->assertCount(3, Article::all());
        $this->assertCount(3, Article::sold(null)->get());
        $this->assertCount(3, Article::sold(-1)->get());

        $sold_articles = Article::sold(1)->get();
        $this->assertCount(1, $sold_articles);
        $this->assertEquals($article_in_paid_order->id, $sold_articles->first()->id);

        $not_sold_articles = Article::sold(0)->get();
        $this->assertCount(2, $not_sold_articles);
        $this->assertEquals([ $article->id, $article_in_cancelled_order->id ], $not_sold_articles->pluck('id')->toArray());
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
}
