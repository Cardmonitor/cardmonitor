<?php

namespace Tests\Feature\Controller\Articles;

use App\Models\Articles\Article;
use App\Models\Cards\Card;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    protected $baseRouteName = 'article';
    protected $baseViewPath = 'article';
    protected $className = Article::class;

    /**
     * @test
     */
    public function guests_can_not_access_the_following_routes()
    {
        $id = factory($this->className)->create()->id;

        $actions = [
            'index' => [],
            'store' => [],
            'show' => ['article' => $id],
            'edit' => ['article' => $id],
            'update' => ['article' => $id],
            'destroy' => ['article' => $id],
        ];
        $this->guestsCanNotAccess($actions);
    }

    /**
     * @test
     */
    public function a_user_can_not_see_things_from_a_different_user()
    {
        $modelOfADifferentUser = factory($this->className)->create();

        $parameters = ['article' => $modelOfADifferentUser->id];

        $this->a_user_can_not_see_models_from_a_different_user(['article' => $modelOfADifferentUser->id]);
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
    public function a_user_can_get_a_paginated_collection_of_models()
    {
        $models = factory($this->className, 3)->create([
            'user_id' => $this->user->id,
        ]);

        $this->getPaginatedCollection();
    }

    /**
     * @test
     */
    public function a_user_can_create_a_model()
    {
        $this->withoutExceptionHandling();

        $this->signIn();

        $card = factory(Card::class)->create();

        $data = [
            'card_id' => $card->id,
            'language_id' => 1,
            'condition' => 'NM',
            'count' => 1,
        ];

        $this->post(route($this->baseRouteName . '.store'), $data)
            ->assertStatus(Response::HTTP_CREATED);

        Arr::forget($data, 'count');

        $this->assertDatabaseHas((new $this->className)->getTable(), [
            'user_id' => $this->user->id,
        ] + $data);
    }

    /**
     * @test
     */
    public function a_user_can_create_a_model_with_defaults()
    {
        $this->withoutExceptionHandling();

        $this->signIn();

        $card = factory(Card::class)->create();

        $data = [
            'card_id' => $card->id,
            'count' => 1,
        ];

        $this->post(route($this->baseRouteName . '.store'), $data)
            ->assertStatus(Response::HTTP_CREATED);

        Arr::forget($data, 'count');

        $this->assertDatabaseHas((new $this->className)->getTable(), [
            'user_id' => $this->user->id,
            'language_id' => Article::DEFAULT_LANGUAGE,
            'condition' => Article::DEFAULT_CONDITION,
        ] + $data);
    }

    /**
     * @test
     */
    public function a_user_can_see_the_show_view()
    {
        $model = $this->createModel();

        $this->getShowViewResponse(['article' => $model->id]);
    }

    /**
     * @test
     */
    public function a_user_can_see_the_edit_view()
    {
        $model = $this->createModel();

        $this->getEditViewResponse(['article' => $model->id]);
    }

    /**
     * @test
     */
    public function a_user_can_update_a_model()
    {
        $model = $this->createModel();

        $this->signIn($this->user);

        $boughtAt = today()->setTime(12, 34, 0);
        $soldAt = today()->setTime(15, 43, 0)->addDays(7);
        $data = [
            'condition' => 'NM',
            'language_id' => 1,
            'unit_cost_formatted' => '1,23',
            'unit_price_formatted' => '2,34',
            'provision_formatted' => '0,02',
            // 'bought_at_formatted' => $boughtAt->format('d.m.Y H:i'),
            // 'sold_at_formatted' => $soldAt->format('d.m.Y H:i'),
        ];

        $response = $this->put(route($this->baseRouteName . '.update', ['article' => $model->id]), $data)
            ->assertStatus(Response::HTTP_OK)
            ->assertSessionHasNoErrors();

        $data['unit_cost'] = 1.230000;
        $data['unit_price'] = 2.340000;
        $data['provision'] = 0.020000;
        // $data['bought_at'] = $boughtAt->format('Y-m-d H:i:s');
        // $data['sold_at'] = $soldAt->format('Y-m-d H:i:s');

        Arr::forget($data, [
            'bought_at_formatted',
            'sold_at_formatted',
            'unit_cost_formatted',
            'unit_price_formatted',
            'provision_formatted',
        ]);

        $this->assertDatabaseHas($model->getTable(), [
            'id' => $model->id,
        ] + $data);
    }

    /**
     * @test
     */
    public function a_user_can_delete_a_model()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');

        $model = $this->createModel();

        $this->deleteModel($model, ['article' => $model->id])
            ->assertRedirect();
    }
}
