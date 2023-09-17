<?php

namespace Tests\Unit\Models\Cards;

use Mockery;
use Tests\TestCase;
use App\Models\Cards\Card;
use App\Models\Games\Game;
use Cardmonitor\Cardmarket\Product;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use App\Models\Localizations\Language;
use Tests\Traits\RelationshipAssertions;
use Tests\Support\Snapshots\JsonSnapshot;
use App\Models\Localizations\Localization;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CardTest extends TestCase
{
    use RelationshipAssertions;

    /**
     * @test
     */
    public function it_has_many_localizations()
    {
        $model = factory(Card::class)->create();

        $this->assertEquals(MorphMany::class, get_class($model->localizations()));

        $this->assertCount(1, $model->fresh()->localizations);

        $model->localizations()
            ->create(factory(Localization::class)->make([
                'language_id' => Language::DEFAULT_ID,
            ])->toArray())
            ->save();

        $this->assertCount(2, $model->fresh()->localizations);
    }

    /**
     * @test
     */
    public function a_default_localization_is_created_after_model_is_created()
    {
        $model = factory(Card::class)->create();

        $this->assertCount(1, $model->localizations);

        $this->assertDatabaseHas('localizations', [
            'localizationable_type' => Card::class,
            'localizationable_id' => $model->id,
            'language_id' => Language::DEFAULT_ID,
            'name' => $model->name,
        ]);
    }

    /**
     * @test
     */
    public function it_belongs_to_an_expansion()
    {
        $related = factory(Expansion::class)->create();
        $model = factory(Card::class)->create();

        $this->assertBelongsTo($model, $related, 'expansion');
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_be_created_from_cardmarket()
    {
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
        $this->assertEquals($cardmarket_product['idProduct'], $card->cardmarket_product_id);
        $this->assertEquals($cardmarket_product['enName'], $card->name);
        $this->assertEquals($cardmarket_product['image'], $card->image);
        $this->assertEquals($cardmarket_product['website'], $card->website);
        $this->assertEquals($cardmarket_product['countReprints'], $card->reprints_count);
        $this->assertEquals($cardmarket_product['rarity'], $card->rarity);
        $this->assertEquals($cardmarket_product['number'], $card->number);
        $this->assertEquals($cardmarket_product['idGame'], $card->game_id);
        $this->assertCount(count($cardmarket_product['localization']), $card->localizations);
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_be_imported()
    {
        $cardmarket_product_id = 265882;
        $cardmarket_product_response = JsonSnapshot::get('tests/snapshots/cardmarket/product/' . $cardmarket_product_id . '.json', function () use ($cardmarket_product_id) {
            return App::make('CardmarketApi')->product->get($cardmarket_product_id);
        });
        $cardmarket_product = $cardmarket_product_response['product'];

        $cardmarket_product_mock = Mockery::mock('overload:' . Product::class);
        $cardmarket_product_mock->shouldReceive('get')
            ->with($cardmarket_product_id)
            ->andReturn($cardmarket_product_response);

        $cardmarket_expansion_id = $cardmarket_product_response['product']['expansion']['idExpansion'];
        $cardmarket_expansion_response = JsonSnapshot::get('tests/snapshots/cardmarket/expansion/singles/' . $cardmarket_expansion_id . '.json', function () use ($cardmarket_expansion_id) {
            return App::make('CardmarketApi')->expansion->singles($cardmarket_expansion_id);
        });
        $cardmarket_expansion_mock = Mockery::mock('overload:' . \Cardmonitor\Cardmarket\Expansion::class);
        $cardmarket_expansion_mock->shouldReceive('singles')
            ->with($cardmarket_expansion_id)
            ->andReturn($cardmarket_expansion_response);

        $this->assertDatabaseMissing('cards', [
            'id' => $cardmarket_product_id,
        ]);

        $this->assertDatabaseMissing('expansions', [
            'id' => $cardmarket_expansion_id,
        ]);

        $model = Card::import($cardmarket_product_id);

        $this->assertDatabaseHas('cards', [
            'id' => $cardmarket_product_id,
        ]);

        $this->assertDatabaseHas('expansions', [
            'id' => $cardmarket_expansion_id,
        ]);

        $model = Card::import($cardmarket_product_id);

        $this->assertEquals(1, Card::where('id', $cardmarket_product_id)->count());
        $this->assertEquals(1, Expansion::where('id', $cardmarket_expansion_id)->count());

        $this->assertEquals($cardmarket_product['idProduct'], $model->cardmarket_product_id);
        $this->assertEquals($cardmarket_product['idMetaproduct'], $model->cardmarket_meta_product_id);
        $this->assertEquals($cardmarket_product['categoryName'], $model->category_name);
        $this->assertEquals($cardmarket_product['countReprints'], $model->reprints_count);
        $this->assertEquals($cardmarket_product['expansion']['idExpansion'], $model->expansion_id);
        $this->assertEquals($cardmarket_product['idGame'], $model->game_id);
        $this->assertEquals($cardmarket_product['image'], $model->image);
        $this->assertEquals($cardmarket_product['idGame'], $model->game_id);
        $this->assertEquals($cardmarket_product['enName'], $model->name);
        $this->assertEquals($cardmarket_product['number'], $model->number);
        $this->assertEquals($cardmarket_product['rarity'], $model->rarity);
        $this->assertEquals($cardmarket_product['countReprints'], $model->reprints_count);
        $this->assertEquals($cardmarket_product['website'], $model->website);

        Mockery::close();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function a_product_without_expansion_can_be_imported()
    {
        $cardmarket_product_id = 200002;
        $cardmarket_product_response = JsonSnapshot::get('tests/snapshots/cardmarket/product/' . $cardmarket_product_id . '.json', function () use ($cardmarket_product_id) {
            return App::make('CardmarketApi')->product->get($cardmarket_product_id);
        });

        $cardmarket_product_mock = Mockery::mock('overload:' . \Cardmonitor\Cardmarket\Product::class);
        $cardmarket_product_mock->shouldReceive('get')
            ->with($cardmarket_product_id)
            ->andReturn($cardmarket_product_response);

        $this->assertDatabaseMissing('cards', [
            'id' => $cardmarket_product_id,
        ]);

        $model = Card::import($cardmarket_product_id);

        $this->assertDatabaseHas('cards', [
            'id' => $cardmarket_product_id,
        ]);

        $model = Card::import($cardmarket_product_id);

        $this->assertEquals(1, Card::where('id', $cardmarket_product_id)->count());
        $this->assertEquals(0, Expansion::count());
    }

    /**
     * @test
     */
    public function it_knows_if_prices_are_to_old()
    {
        $model = factory(Card::class)->create([
            'prices_updated_at' => null,
        ]);

        $model = factory(Card::class)->create([
            'prices_updated_at' => now()->subHours(3),
        ]);

        $this->assertFalse(Card::hasLatestPrices());

        $model->update([
            'prices_updated_at' => now(),
        ]);

        $this->assertTrue(Card::hasLatestPrices());
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_can_be_updated_from_skryfall_by_cardmarket_id()
    {
        $cardmarket_id = 265882;
        $skryfall_card_id = '1c2b1eeb-6cc9-48a7-a068-afa1011c45f2';

        $expansion = factory(Expansion::class)->create([
            'name' => 'Born of the Gods',
        ]);

        $card = factory(Card::class)->create([
            'cardmarket_product_id' => $cardmarket_id,
            'expansion_id' => $expansion->id,
        ]);

        $skyfall_card_response = JsonSnapshot::get('tests/snapshots/skryfall/cards/' . $skryfall_card_id . '.json', function () use ($skryfall_card_id) {
            return App::make('SkryfallApi')->cards->findByCardmarketId($skryfall_card_id);
        });
        $skryfall_card_mock = Mockery::mock('overload:' . \Cardmonitor\Skryfall\Card::class);
        $skryfall_card_mock->shouldReceive('findByCardmarketId')
            ->with($cardmarket_id)
            ->andReturn($skyfall_card_response);

        $card->updateFromSkryfallByCardmarketId($cardmarket_id);

        $this->assertEquals($skryfall_card_id, $card->skryfall_card_id);
        $this->assertEquals($skyfall_card_response['name'], $card->name);
        $this->assertEquals($skyfall_card_response['color_identity'], $card->color_identity);
        $this->assertEquals($skyfall_card_response['colors'], $card->colors);
        $this->assertEquals($skyfall_card_response['type_line'], $card->type_line);
        $this->assertEquals($skyfall_card_response['collector_number'], $card->number);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_has_a_color_name_attribute()
    {
        $model = new Card();

        $this->assertEquals('Not Available', $model->color_name);

        $model->color_order_by = 'C';
        $this->assertEquals('Colorless', $model->color_name);

        $model->color_order_by = 'W';
        $this->assertEquals('White', $model->color_name);

        $model->color_order_by = 'M';
        $this->assertEquals('Multicolor', $model->color_name);

        $model->color_order_by = 'L';
        $this->assertEquals('Land', $model->color_name);
    }

    /**
     * @test
     */
    public function it_knows_its_sku()
    {
        $card_without_expansion = factory(Card::class)->create([
            'expansion_id' => null,
        ]);

        $this->assertEquals('', $card_without_expansion->sku);

        $card = factory(Card::class)->create([
            'game_id' => Game::ID_MAGIC,
        ]);
        $this->assertEquals('A11360', $card->sku);

        $card = factory(Card::class)->create([
            'game_id' => Game::ID_POKEMON,
        ]);
        $this->assertEquals('A11359', $card->sku);

        $card = factory(Card::class)->create([
            'game_id' => Game::ID_FLESH_AND_BLOOD,
        ]);
        $this->assertEquals('A11433', $card->sku);

        $card = factory(Card::class)->create([
            'game_id' => Game::ID_YUGIOH,
        ]);
        $this->assertEquals('', $card->sku);
    }

    /**
     * @test
     */
    public function it_can_be_searched_by_name_or_number()
    {
        $card = factory(Card::class)->create([
            'name' => 'Test Card',
            'number' => '123',
        ]);

        $this->assertCount(1, Card::search('Test Card', Language::DEFAULT_ID)->get());
        $this->assertCount(0, Card::search('Not available', Language::DEFAULT_ID)->get());
        $this->assertCount(1, Card::search('123', Language::DEFAULT_ID)->get());
        $this->assertCount(0, Card::search('321', Language::DEFAULT_ID)->get());
    }

    /**
     * @test
     */
    public function it_knwos_if_it_is_a_singles_product()
    {
        $card = factory(Card::class)->create([
            'category_name' => null
        ]);
        $this->assertTrue($card->is_single);

        $single_category_names = [
            'Magic Single',
            'Yugioh Single',
            'WOW Single',
            'Cardfight!! Vanguard Single',
            'Star Wars: Destiny Singles',
            'Dragon Ball Super Singles',
        ];

        foreach ($single_category_names as $category_name) {
            $card->update([
                'category_name' => $category_name
            ]);
            $this->assertTrue($card->is_single, $category_name);
        }

        $other_category_names = [
            'MtG Set',
            'Yugioh Booster',
            'Magic Intropack',
            'Magic Theme Deck Display',
            'WOW Booster',
        ];

        foreach ($other_category_names as $category_name) {
            $card->update([
                'category_name' => $category_name
            ]);
            $this->assertFalse($card->is_single, $category_name);
        }
    }
}
