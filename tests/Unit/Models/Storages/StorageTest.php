<?php

namespace Tests\Unit\Models\Storages;

use App\Models\Articles\Article;
use App\Models\Storages\Content;
use App\Models\Storages\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\RelationshipAssertions;

class StorageTest extends TestCase
{
    use RelationshipAssertions;

    /**
     * @test
     */
    public function it_sets_its_full_name()
    {
        $parent = factory(Storage::class)->create([
            'user_id' => $this->user->id,
        ]);
        $child = factory(Storage::class)->create([
            'user_id' => $this->user->id,
        ]);

        $child2 = factory(Storage::class)->create([
            'user_id' => $this->user->id,
        ]);

        $child->appendToNode($parent)
            ->save();

        $child2->appendToNode($child)
            ->save();

        $this->assertEquals($parent->name, $parent->full_name);
        $this->assertEquals($parent->name . '/' . $child->name, $child->full_name);
        $this->assertEquals($parent->name . '/' . $child->name . '/' . $child2->name, $child2->full_name);
    }

    /**
     * @test
     */
    public function it_can_set_its_descendants_full_names()
    {
        $parent = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'parent',
        ]);

        $child = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'child 1',
        ]);

        $child2 = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => 'child 2',
        ]);

        $child->appendToNode($parent)
            ->save();

        $child2->appendToNode($child)
            ->save();

        $newName = 'New Parent Name';
        $parent->update([
            'name' => $newName,
        ]);

        $child = $child->fresh();
        $child2 = $child2->fresh();

        $this->assertEquals($newName . '/' . $child->name, $child->full_name);
        $this->assertEquals($newName . '/' . $child->name . '/' . $child2->name, $child2->full_name);
    }

    /**
     * @test
     */
    public function it_has_many_articles()
    {
        $model = factory(Storage::class)->create();
        $related = factory(Article::class)->create([
            'user_id' => $model->user_id,
            'storage_id' => $model->id
        ]);

        $this->assertHasMany($model, $related, 'articles');
    }

    /**
     * @test
     */
    public function it_has_many_contents()
    {
        $model = factory(Storage::class)->create();
        $related = factory(Content::class)->create([
            'user_id' => $model->user_id,
            'storage_id' => $model->id
        ]);

        $this->assertHasMany($model, $related, 'contents');
    }

    /**
     * @test
     */
    public function it_finds_the_no_storage()
    {
        $this->assertNull(Storage::noStorage($this->user->id)->first());

        $no_storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'name' => Storage::NAME_NO_STORAGE,
        ]);

        $storage = Storage::noStorage($this->user->id)->first();
        $this->assertInstanceOf(Storage::class, $storage);
        $this->assertEquals($no_storage->id, $storage->id);

        $storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
        ]);

        $storage = Storage::noStorage($this->user->id)->first();
        $this->assertInstanceOf(Storage::class, $storage);
        $this->assertEquals($no_storage->id, $storage->id);
    }

    /**
     * @test
     */
    public function it_knows_its_open_slot_count()
    {
        $storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'slots' => 0,
        ]);

        $this->assertEquals(0, $storage->open_slots_count);

        $storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'slots' => 100,
        ]);

        $this->assertEquals(100, $storage->open_slots_count);

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
        ]);

        $article->setStorage($storage, 1)->save();

        $this->assertEquals(99, $storage->open_slots_count);
    }

    /**
     * @test
     */
    public function it_knows_its_open_slots()
    {
        $storage = factory(Storage::class)->create([
            'user_id' => $this->user->id,
            'slots' => 5,
        ]);

        $this->assertCount(5, Storage::openSlots($storage->id));

        $article = factory(Article::class)->create([
            'user_id' => $this->user->id,
            'storage_id' => $storage->id,
            'slot' => 1,
        ]);

        $this->assertCount(4, Storage::openSlots($storage->id));
    }
}
