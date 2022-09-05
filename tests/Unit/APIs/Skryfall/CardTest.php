<?php

namespace Tests\Unit\APIs\Skryfall;

use Tests\TestCase;
use App\APIs\Skryfall\Card;
use Illuminate\Support\Carbon;
use App\APIs\Skryfall\CardCollection;

class CardTest extends TestCase
{
    /**
     * @test
     */
    public function it_finds_a_card_by_code_and_number()
    {
        $this->markTestSkipped();

        $model = Card::findByCodeAndNumber('mmq', 1);
        $this->assertInstanceOf(Card::class, $model);
    }

    /**
     * @test
     */
    public function it_has_the_color_order_by_attribute()
    {
        $model = new Card();

        $this->assertEquals('C', $model->color_order_by);

        $model->colors = [];
        $this->assertEquals('C', $model->color_order_by);

        $model->colors = ['W'];
        $this->assertEquals('W', $model->color_order_by);

        $model->colors = ['W', 'U'];
        $this->assertEquals('M', $model->color_order_by);

        $model->type_line = 'Land';
        $model->colors = ['W'];
        $this->assertEquals('L', $model->color_order_by);
    }

    /**
     * @test
     */
    public function it_has_the_color_order_by_attribute_for_cards_with_two_faces()
    {
        $model = Card::findByCardmarketId(494774);
        $this->assertEquals('C', $model->color_order_by);
    }

    /**
     * @test
     */
    public function it_finds_a_card_by_cardmarket_id()
    {
        // $this->markTestSkipped();

        $model = Card::findByCardmarketId(301775);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals('c3f1f41e-98fc-4f6b-b287-c8899dff8ab0', $model->id);
        $this->assertEquals('B', $model->color_order_by);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/small/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.jpg?1562563557', $model->image_uri_small);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/normal/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.jpg?1562563557', $model->image_uri_normal);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/large/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.jpg?1562563557', $model->image_uri_large);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/png/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.png?1562563557', $model->image_uri_png);
        $this->assertEquals(96, $model->collector_number);
    }

    /**
     * @test
     */
    public function it_has_an_image_if_the_card_has_two_faces()
    {
        // $this->markTestSkipped();

        $model = Card::findByCardmarketId(496410);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals('609d3ecf-f88d-4268-a8d3-4bf2bcf5df60', $model->id);
        $this->assertEquals('B', $model->color_order_by);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/small/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.jpg?1604195984', $model->image_uri_small);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/normal/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.jpg?1604195984', $model->image_uri_normal);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/large/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.jpg?1604195984', $model->image_uri_large);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/png/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.png?1604195984', $model->image_uri_png);
        $this->assertEquals(111, $model->collector_number);

        $model = Card::findByCardmarketId(497835);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals('531d60ad-39f6-4d79-b276-ec70636b123b', $model->id);
        $this->assertEquals('M', $model->color_order_by);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/small/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.jpg?1599766870', $model->image_uri_small);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/normal/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.jpg?1599766870', $model->image_uri_normal);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/large/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.jpg?1599766870', $model->image_uri_large);
        $this->assertEquals('https://c1.scryfall.com/file/scryfall-cards/png/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.png?1599766870', $model->image_uri_png);
        $this->assertEquals(239, $model->collector_number);
    }

    /**
     * @test
     */
    public function it_returns_null_if_not_found()
    {
        $this->markTestSkipped();

        $model = Card::findByCodeAndNumber('mmq', 99999);
        $this->assertNull($model);
    }

    /**
     * @test
     */
    public function it_gets_colors_string_attribute()
    {
        $this->markTestSkipped();

        $model = new Card();
        $model->colors = ['b', 'w'];
        $this->assertEquals('b, w', $model->colors_string);
    }

    /**
     * @test
     */
    public function it_gets_color_identity_string_attribute()
    {
        $this->markTestSkipped();

        $model = new Card();
        $model->color_identity = ['b', 'w'];
        $this->assertEquals('b, w', $model->color_identity_string);
    }

    /**
     * @test
     */
    public function it_has_a_release_date()
    {
        $this->markTestSkipped();

        $model = new Card();
        $model->released_at = '2017-04-28';
        $this->assertInstanceOf(Carbon::class, $model->released_at);
    }

    /**
     * @test
     */
    public function it_gets_all_cards_from_a_set()
    {
        $this->markTestSkipped();

        $collection = Card::fromSet('mmq');
        $this->assertInstanceOf(CardCollection::class, $collection);
        $this->assertCount(350, $collection);
    }
}
