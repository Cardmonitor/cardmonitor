<?php

namespace Tests\Feature\Controller\Users;

use Tests\TestCase;
use App\Support\BackgroundTasks;
use Illuminate\Support\Facades\Log;

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
    public function it_can_show_the_contents_of_the_log_for_a_task()
    {
        $BackgroundTasks = BackgroundTasks::make();
        $path = 'logs/jobs/test.log';

        $this->signIn();

        $this->getJson(route('user.backgroundtasks.show', [
            'task' => 'test'
        ]))
            ->assertOk()
            ->assertSee('');

        $Logger = Log::build([
            'driver' => 'single',
            'path' => storage_path($path),
        ]);

        $Logger->info('test');

        $BackgroundTasks->put('test', $path);

        $this->getJson(route('user.backgroundtasks.show', [
            'task' => 'test'
        ]))
            ->assertOk()
            ->assertSee('test');

        $this->deleteJson(route('user.backgroundtasks.destroy', [
            'task' => 'test'
        ]))
            ->assertOk()
            ->assertJson([
                //
            ]);

        unlink(storage_path($path));
        $BackgroundTasks->forget('test');
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
