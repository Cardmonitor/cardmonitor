<?php

namespace Tests\Unit\Models\Articles;

use Tests\TestCase;
use Tests\Traits\AttributeAssertions;
use Tests\Traits\RelationshipAssertions;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ExternalIdTest extends TestCase
{
    use AttributeAssertions, RelationshipAssertions;

    /**
     * @test
     */
    public function it_belongs_to_an_article()
    {
        $model = \App\Models\Articles\ExternalId::factory()->create();
        $this->assertEquals(BelongsTo::class, get_class($model->article()));
    }
}
