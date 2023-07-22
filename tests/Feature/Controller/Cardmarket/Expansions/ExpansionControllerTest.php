<?php

namespace Tests\Feature\Controller\Cardmarket\Expansions;

use Mockery;
use Tests\TestCase;
use Illuminate\Http\Response;
use App\Models\Expansions\Expansion;
use Cardmonitor\Cardmarket\Expansion as CardmarketExpansion;

class ExpansionControllerTest extends TestCase
{
    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function a_user_can_see_the_cardmarket_response()
    {
        $cardmarket_expansion_response = json_decode(file_get_contents('tests/snapshots/cardmarket/expansion/singles/1249.json'), true);

        $model = factory(Expansion::class)->create([
            'id' => $cardmarket_expansion_response['expansion']['idExpansion'],
            'game_id' => $cardmarket_expansion_response['expansion']['idGame'],
        ]);

        $stockMock = Mockery::mock('overload:' . CardmarketExpansion::class);
        $stockMock->shouldReceive('singles')
            ->with($model->id)
            ->andReturn($cardmarket_expansion_response);

        $this->signIn();

        $response = $this->get(route('expansions.cardmarket.show', [
            'expansion' => $model->id,
        ]));
        $response->assertStatus(Response::HTTP_OK);

        $this->assertEquals($cardmarket_expansion_response, $response->json());

        Mockery::close();
    }
}
