<?php

namespace Tests\Feature\Commands\Expansion\Imports;

use Mockery;
use Tests\TestCase;
use App\Models\Games\Game;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Artisan;
use Cardmonitor\Cardmarket\Expansion as CardmarketExpansion;

class MissingCommandTest extends TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_finds_missing_expansions()
    {
        $game = factory(Game::class)->create([
            'id' => Game::ID_MAGIC,
            'is_importable' => true,
        ]);

        $cardmarket_expansion_response = json_decode(file_get_contents('tests/snapshots/cardmarket/expansion/game/mtg.json'), true);
        $missing_cardmarket_expansion = $cardmarket_expansion_response['expansion'][0];

        foreach ($cardmarket_expansion_response['expansion'] as $key => $cardmarket_expansion) {
            if ($key === 0) {
                continue;
            }
            factory(Expansion::class)->create([
                'cardmarket_expansion_id' => $cardmarket_expansion['idExpansion'],
                'game_id' => Game::ID_MAGIC,
            ]);
        }

        $this->assertDatabaseMissing('expansions', [
            'cardmarket_expansion_id' => $missing_cardmarket_expansion['idExpansion'],
        ]);

        $expansion_mock = Mockery::mock('overload:' . CardmarketExpansion::class);
        $expansion_mock->shouldReceive('find')
            ->with($game->id)
            ->andReturn($cardmarket_expansion_response);

        $expansion_mock->shouldReceive('singles')
            ->with($missing_cardmarket_expansion['idExpansion'])
            ->andReturn([
                'expansion' => $missing_cardmarket_expansion,
                'single' => [],
            ]);

        Artisan::call('expansion:imports:missing', [
            '--queue' => true
        ]);

        $this->assertDatabaseHas('expansions', [
            'cardmarket_expansion_id' => $missing_cardmarket_expansion['idExpansion'],
        ]);

        Mockery::close();
    }
}
