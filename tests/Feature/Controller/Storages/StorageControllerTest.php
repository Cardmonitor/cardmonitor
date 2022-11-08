<?php

namespace Tests\Feature\Controller\Storages;

use App\Models\Storages\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Tests\TestCase;

class StorageControllerTest extends TestCase
{
    protected $baseRouteName = 'storages';
    protected $baseViewPath = 'storage';
    protected $className = Storage::class;

    /**
     * @test
     */
    public function guests_can_not_access_the_following_routes()
    {
        $id = factory($this->className)->create()->id;

        $actions = [
            'index' => [],
            'create' => [],
            'store' => [],
            'show' => ['storage' => $id],
            'edit' => ['storage' => $id],
            'update' => ['storage' => $id],
            'destroy' => ['storage' => $id],
        ];
        $this->guestsCanNotAccess($actions);
    }

    /**
     * @test
     */
    public function a_user_can_not_see_things_from_a_different_user()
    {
        $modelOfADifferentUser = factory($this->className)->create();

        $this->a_user_can_not_see_models_from_a_different_user(['storage' => $modelOfADifferentUser->id]);
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
            'user_id' => $this->user->id,
        ]);

        $this->getCollection();
    }

    /**
     * @test
     */
    public function a_user_can_create_a_model()
    {
        $this->withoutExceptionHandling();

        $this->signIn();

        $data = [
            'name' => 'New Model',
        ];

        $this->post(route($this->baseRouteName . '.store'), $data)
            ->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas((new $this->className)->getTable(), [
            'user_id' => $this->user->id,
            'full_name' => 'New Model',
        ] + $data);
    }

    /**
     * @test
     */
    public function a_user_can_see_the_show_view()
    {
        $this->withoutExceptionHandling();

        $model = $this->createModel();

        $this->getShowViewResponse(['storage' => $model->id]);
    }

    /**
     * @test
     */
    public function a_user_can_see_the_edit_view()
    {
        $model = $this->createModel();

        $this->getEditViewResponse(['storage' => $model->id]);
    }

    /**
     * @test
     */
    public function a_user_can_update_a_model()
    {
        // $this->withoutExceptionHandling();

        $model = $this->createModel();

        $this->signIn($this->user);

        $data = [
            'name' => 'Updated Model',
            'slots' => 100,
            'parent_id' => null,
        ];

        $response = $this->put(route($this->baseRouteName . '.update', ['storage' => $model->id]), $data)
            ->assertStatus(Response::HTTP_FOUND)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas($model->getTable(), [
            'id' => $model->id,
        ] + $data);
    }

    /**
     * @test
     */
    public function a_user_can_delete_a_model()
    {
        $this->withoutExceptionHandling();

        $model = $this->createModel();

        $this->deleteModel($model, ['storage' => $model->id])
            ->assertRedirect();
    }
}
