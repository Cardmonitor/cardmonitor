<?php

namespace Tests\Feature\Commands\Article;

use Mockery;
use Tests\TestCase;
use App\Models\Games\Game;
use App\Support\Users\CardmarketApi;
use Illuminate\Support\Facades\Artisan;

class SyncCommandTest extends TestCase
{
    /**
     * @test
     */
    public function it_syncs_articles()
    {
        $this->markTestSkipped();

        $game = factory(Game::class)->create([
            'id' => Game::ID_MAGIC,
            'name' => 'Magic the Gathering',
            'abbreviation' => 'MtG',
            'is_importable' => true,
        ]);

        $filename = $this->user->id . '-stock-' . Game::ID_MAGIC . '.csv';
        $test_filepath = 'tests/snapshots/cardmarket/articles/stock-1.csv';
        copy($test_filepath, storage_path('app/' . $filename));

        $CardmarketApiMock = Mockery::mock($this->user->cardmarket_api)
            // ->makePartial()
            ->shouldReceive('downloadStockFile')
            ->withArgs([
                $this->user->id,
                Game::ID_MAGIC,
            ])
            ->andReturn($filename);

        Artisan::call('article:sync', [
            'user' => $this->user->id,
        ]);

        // assert articles exist
    }
}
