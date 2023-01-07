<?php

namespace Tests\Feature\Controller\Articles;

use App\Models\Articles\Article;
use Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use App\Models\Articles\StoringHistory;
use Illuminate\Database\Eloquent\Model;

class StoringHistoryControllerTest extends TestCase
{
    protected $baseRouteName = 'article.storing_history';
    protected $baseViewPath = 'article.storing_history';
    protected $className = StoringHistory::class;

    /**
     * @test
     */
    public function guests_can_not_access_the_following_routes()
    {
        $id = $this->className::factory()->create()->id;

        $actions = [
            'index' => [],
            'show' => ['storing_history' => $id],
        ];
        $this->guestsCanNotAccess($actions);
    }

    /**
     * @test
     */
    public function a_user_can_not_see_things_from_a_different_user()
    {
        $modelOfADifferentUser = $this->className::factory()->create();

        $this->signIn();

        $parameters = ['storing_history' => $modelOfADifferentUser->id];

        $this->a_different_user_gets_a_403('show', 'get', $parameters);
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
        $models = $this->className::factory()->times(3)->create([
            'user_id' => $this->user->id,
        ]);

        $this->getPaginatedCollection();
    }

    /**
     * @test
     */
    public function a_user_can_see_the_show_view()
    {
        $this->withoutExceptionHandling();

        $model = $this->createModel();

        $this->getShowViewResponse(['storing_history' => $model->id]);
    }

    /**
     * @test
     */
    public function a_user_can_get_a_collection_of_articles()
    {
        $model = $this->createModel();

        factory(Article::class, 3)->create([
            'user_id' => $this->user->id,
            'storing_history_id' => $model->id,
        ]);

        $this->signIn();

        $response = $this->json('get', route($this->baseRouteName . '.show', ['storing_history' => $model->id]), []);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'current_page',
                'data',
                'total',
            ])
            ->assertJsonCount(3, 'data');
    }

    protected function createModel(array $attributes = []): Model
    {
        return $this->className::factory()->create([
            'user_id' => $this->user->id,
        ] + $attributes)->fresh();
    }
}
