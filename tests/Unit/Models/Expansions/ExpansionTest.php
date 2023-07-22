<?php

namespace Tests\Unit\Models\Expansions;

use App\Models\Cards\Card;
use App\Models\Expansions\Expansion;
use App\Models\Localizations\Language;
use App\Models\Localizations\Localization;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\RelationshipAssertions;

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
        $cardmarketExpansion = [
            "idExpansion" => 1408,
            "enName" => "Filler Cards",
            "localization" => [
                0 => [
                    "name" => "Filler Cards",
                    "idLanguage" => "1",
                    "languageName" => "English",
                ],
                1 => [
                    "name" => "Filler Cards",
                    "idLanguage" => "2",
                    "languageName" => "French",
                ],
                2 => [
                    "name" => "Filler Cards",
                    "idLanguage" => "3",
                    "languageName" => "German",
                ],
                3 => [
                    "name" => "Filler Cards",
                    "idLanguage" => "4",
                    "languageName" => "Spanish",
                ],
                4 => [
                    "name" => "Filler Cards",
                    "idLanguage" => "5",
                    "languageName" => "Italian",
                ],
            ],
            "abbreviation" => "MGFC",
            "icon" => 203,
            "releaseDate" => "1900-01-01T00:00:00+0100",
            "isReleased" => true,
            "idGame" => "1",
            "links" => [
                0 => [
                    "rel" => "singles",
                    "href" => "/expansions/1408/singles",
                    "method" => "GET",
                ],
            ],
        ];
        $expansion = Expansion::createFromCardmarket($cardmarketExpansion);
        $this->assertEquals(1408, $expansion->cardmarket_expansion_id);
        $this->assertEquals('Filler Cards', $expansion->name);
        $this->assertEquals('MGFC', $expansion->abbreviation);
        $this->assertTrue($expansion->is_released);
        $this->assertEquals('1900-01-01', $expansion->released_at->format('Y-m-d'));
        $this->assertEquals(1, $expansion->game_id);
        $this->assertCount(5, $expansion->localizations);
    }

    /**
     * @test
     */
    public function it_can_be_imported()
    {
        $this->markTestSkipped();

        $cardmarketExpansionId = 1408;
        $this->assertDatabaseMissing('expansions', [
            'id' => $cardmarketExpansionId,
        ]);

        $model = Expansion::import($cardmarketExpansionId);

        $this->assertDatabaseHas('expansions', [
            'id' => $cardmarketExpansionId,
        ]);

        $model = Expansion::import($cardmarketExpansionId);

        $this->assertEquals(1, Expansion::where('id', $cardmarketExpansionId)->count());
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
