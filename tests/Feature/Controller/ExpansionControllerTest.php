<?php

namespace Tests\Feature\Controller;

use Mockery;
use Tests\TestCase;
use App\Models\Games\Game;
use Illuminate\Http\Response;
use App\Models\Expansions\Expansion;
use Illuminate\Support\Facades\Artisan;
use Cardmonitor\Cardmarket\Expansion as CardmarketExpansion;

class ExpansionControllerTest extends TestCase
{
    protected $baseRouteName = 'expansions';
    protected $baseViewPath = 'expansion';
    protected $className = Expansion::class;

    /**
     * @test
     */
    public function guests_can_not_access_the_following_routes()
    {
        $id = factory($this->className)->create()->id;

        $actions = [
            'index' => [],
            'store' => [],
            'update' => ['expansion' => $id],
        ];
        $this->guestsCanNotAccess($actions);
    }

    /**
     * @test
     */
    public function a_user_can_see_the_index_view()
    {
        $this->getIndexViewResponse();
    }

    /**
     * @test
     */
    public function a_user_can_get_a_collection_of_models()
    {
        $models = factory($this->className, 3)->create([
            'game_id' => Game::ID_MAGIC,
        ]);

        $this->getPaginatedCollection();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function a_user_can_create_a_model()
    {
        $cardmarket_expansion_response = json_decode(file_get_contents('tests/snapshots/cardmarket/expansion/game/mtg.json'), true);
        $expansion = $cardmarket_expansion_response['expansion'][0];

        Artisan::shouldReceive('queue')
            ->once()
            ->with(
                'expansion:import',
                [
                    'expansion' => $expansion['idExpansion'],
                ]
            );

        $stockMock = Mockery::mock('overload:' . CardmarketExpansion::class);
        $stockMock->shouldReceive('find')
            ->with($expansion['idGame'])
            ->andReturn($cardmarket_expansion_response);

        $this->signIn();

        $data = [
            'abbreviation' => $expansion['abbreviation'],
            'game_id' => $expansion['idGame'],
        ];

        $this->post(route($this->baseRouteName . '.store'), $data)
            ->assertStatus(Response::HTTP_CREATED);

        Mockery::close();
    }

    /**
     * @test
     */
    public function a_user_can_see_the_show_view()
    {
        $model = factory($this->className)->create([
            'game_id' => Game::ID_MAGIC,
        ]);

        $this->getShowViewResponse(['expansion' => $model->id]);
    }

    /**
     * @test
     */
    public function a_user_can_update_a_model()
    {
        $model = factory($this->className)->create([
            'game_id' => Game::ID_MAGIC,
        ]);

        Artisan::shouldReceive('queue')
            ->once()
            ->with(
                'expansion:import',
                [
                    'expansion' => $model->id,
                ]
            );

        $this->signIn($this->user);

        $response = $this->put(route($this->baseRouteName . '.update', ['expansion' => $model->id]))
            ->assertStatus(Response::HTTP_OK)
            ->assertSessionHasNoErrors();
    }
}
