<?php

namespace Tests\Unit\Models\Expansions;

use Mockery;
use Tests\TestCase;
use App\Models\Cards\Card;
use Illuminate\Support\Facades\App;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Cache;
use App\Models\Localizations\Language;
use Tests\Traits\RelationshipAssertions;
use Tests\Support\Snapshots\JsonSnapshot;
use App\Models\Localizations\Localization;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ExpansionTest extends TestCase
{
    use RelationshipAssertions;

    /**
     * @test
     */
    public function it_has_many_localizations()
    {
        $model = factory(Expansion::class)->create();

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
        $model = factory(Expansion::class)->create();

        $this->assertCount(1, $model->localizations);

        $this->assertDatabaseHas('localizations', [
            'localizationable_type' => Expansion::class,
            'localizationable_id' => $model->id,
            'language_id' => Language::DEFAULT_ID,
            'name' => $model->name,
        ]);
    }

    /**
     * @test
     */
    public function it_has_many_cards()
    {
        $model = factory(Expansion::class)->create();
        $related = factory(Card::class)->create([
            'expansion_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'cards');
    }

    /**
     * @test
     */
    public function it_sets_its_abbrecation_from_image_path()
    {
        $model = new Expansion();

        $model->abbreviationFromCardImagePath = './img/items/1/BNG/265535.jpg';

        $this->assertEquals('bng', $model->abbreviation);
    }

    /**
     * @test
     */
    public function it_can_be_created_from_cardmarket()
    {
        $cardmarket_expansion_id = 1469;
        $cardmarket_expansion_response = JsonSnapshot::get('tests/snapshots/cardmarket/expansion/singles/' . $cardmarket_expansion_id . '.json', function () use ($cardmarket_expansion_id) {
            return App::make('CardmarketApi')->expansion->singles($cardmarket_expansion_id);
        });

        $cardmarket_expansion = $cardmarket_expansion_response['expansion'];

        $expansion = Expansion::createFromCardmarket($cardmarket_expansion);
        $this->assertEquals($cardmarket_expansion['idExpansion'], $expansion->cardmarket_expansion_id);
        $this->assertEquals($cardmarket_expansion['enName'], $expansion->name);
        $this->assertEquals($cardmarket_expansion['abbreviation'], $expansion->abbreviation);
        $this->assertTrue($expansion->is_released);
        $this->assertEquals($cardmarket_expansion['releaseDate'], $expansion->released_at->format('Y-m-d\TH:i:sO'));
        $this->assertEquals($cardmarket_expansion['idGame'], $expansion->game_id);
        $this->assertCount(count($cardmarket_expansion['localization']), $expansion->localizations);
    }

    /**
     * @test
     */
    public function it_can_be_imported()
    {
        $cardmarket_expansion_id = 1469;
        $cardmarket_expansion_response = JsonSnapshot::get('tests/snapshots/cardmarket/expansion/singles/' . $cardmarket_expansion_id . '.json', function () use ($cardmarket_expansion_id) {
            return App::make('CardmarketApi')->expansion->singles($cardmarket_expansion_id);
        });

        $cardmarket_expansion_mock = Mockery::mock('overload:' . \Cardmonitor\Cardmarket\Expansion::class);
        $cardmarket_expansion_mock->shouldReceive('singles')
            ->with($cardmarket_expansion_id)
            ->andReturn($cardmarket_expansion_response);

        $this->assertDatabaseMissing('expansions', [
            'id' => $cardmarket_expansion_id,
        ]);

        $model = Expansion::import($cardmarket_expansion_id);

        $this->assertDatabaseHas('expansions', [
            'id' => $cardmarket_expansion_id,
        ]);

        $model = Expansion::import($cardmarket_expansion_id);

        $this->assertEquals(1, Expansion::where('id', $cardmarket_expansion_id)->count());
    }

    /**
     * @test
     */
    public function it_can_get_a_model_by_abbreviation()
    {
        $abbreviation = 'abc';
        $model = factory(Expansion::class)->create([
            'abbreviation' => strtoupper($abbreviation),
        ]);

        $expansion = Expansion::getByAbbreviation($abbreviation);
        $this->assertEquals($model->id, $expansion->id);
        $this->assertTrue(Cache::has('expansion.' . strtoupper($abbreviation)));
    }

    /**
     * @test
     */
    public function it_knows_if_it_is_presale()
    {
        $model = factory(Expansion::class)->create([
            'released_at' => null,
        ]);
        $this->assertTrue($model->isPresale());

        $model->update([
            'released_at' => now()->addDays(2),
        ]);
        $this->assertTrue($model->isPresale());

        $model->update([
            'released_at' => now()->addDay(),
        ]);
        $this->assertFalse($model->isPresale());
    }

    /**
     * @test
     */
    public function it_knows_its_path()
    {
        $model = factory(Expansion::class)->create();

        $this->assertEquals(route('expansions.show', [
            'expansion' => $model->id,
        ]), $model->path);
    }

    /**
     * @test
     */
    public function it_knows_its_cardmarket_path()
    {
        $model = factory(Expansion::class)->create();

        $this->assertEquals(route('expansions.cardmarket.show', [
            'expansion' => $model->id,
        ]), $model->cardmarket_path);
    }
}
