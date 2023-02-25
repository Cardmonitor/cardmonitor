<?php

namespace Tests\Feature\Controller\Users;

use Tests\TestCase;

class BackgroundTaskControllerTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_list_all_background_tasks()
    {
        $this->signIn();

        $this->getJson(route('user.backgroundtasks.index'))
            ->assertOk()
            ->assertJson([]);
    }

    /**
     * @test
     */
    public function it_can_store_a_background_task()
    {
        $this->signIn();

        $this->postJson(route('user.backgroundtasks.store'), [
            'background_tasks' => [
                'test',
            ],
        ])
            ->assertOk()
            ->assertJson([
                'test' => true,
            ]);
    }

    /**
     * @test
     */
    public function it_can_store_multiple_background_tasks()
    {
        $this->signIn();

        $this->postJson(route('user.backgroundtasks.store'), [
            'background_tasks' => [
                'test',
                'test2'
            ],
        ])
            ->assertOk()
            ->assertJson([
                'test' => true,
                'test2' => true,
            ]);
    }

    /**
     * @test
     */
    public function it_can_delete_a_background_task()
    {
        $this->signIn();

        $this->postJson(route('user.backgroundtasks.store'), [
            'background_tasks' => [
                'test',
            ],
        ])
            ->assertOk()
            ->assertJson([
                'test' => true,
            ]);

        $this->deleteJson(route('user.backgroundtasks.destroy', [
            'task' => 'test'
        ]))
            ->assertOk()
            ->assertJson([
                //
            ]);
    }
}
