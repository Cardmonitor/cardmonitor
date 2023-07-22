<?php

namespace Tests\Unit\APIs\Skryfall;

use Mockery;
use Tests\TestCase;
use App\APIs\Skryfall\Card;
use Illuminate\Support\Carbon;
use App\APIs\Skryfall\CardCollection;
use Illuminate\Http\Response;

class CardTest extends TestCase
{
    /**
     * @test
     */
    public function it_finds_a_card_by_code_and_number()
    {
        $set_code = 'mmq';
        $card_number = 1;
        $skryfall_card_id = '8fa2ecf9-b53c-4f1d-9028-ca3820d043cb';

        $response = json_decode(file_get_contents('tests/snapshots/skryfall/cards/' . $skryfall_card_id . '.json'), true);

        $card_mock = Mockery::mock('overload:' . \Cardmonitor\Skryfall\Card::class);
        $card_mock->shouldReceive('findByCodeAndNumber')
            ->withArgs([
                $set_code,
                $card_number
            ])
            ->andReturn($response);

        $model = Card::findByCodeAndNumber($set_code, $card_number);
        $this->assertInstanceOf(Card::class, $model);

        Mockery::close();
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
        $cardmarket_id = 494774;
        $skryfall_card_id = '0511e232-2a72-40f5-a400-4f7ebc442d17';

        $this->mockFindByCardmarketId($cardmarket_id, $skryfall_card_id);

        $model = Card::findByCardmarketId($cardmarket_id);
        $this->assertEquals('C', $model->color_order_by);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_finds_a_card_by_cardmarket_id()
    {
        $cardmarket_id = 301775;
        $skryfall_card_id = 'c3f1f41e-98fc-4f6b-b287-c8899dff8ab0';

        $this->mockFindByCardmarketId($cardmarket_id, $skryfall_card_id);

        $model = Card::findByCardmarketId($cardmarket_id);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals($skryfall_card_id, $model->id);
        $this->assertEquals('B', $model->color_order_by);
        $this->assertEquals('https://cards.scryfall.io/small/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.jpg?1562563557', $model->image_uri_small);
        $this->assertEquals('https://cards.scryfall.io/normal/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.jpg?1562563557', $model->image_uri_normal);
        $this->assertEquals('https://cards.scryfall.io/large/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.jpg?1562563557', $model->image_uri_large);
        $this->assertEquals('https://cards.scryfall.io/png/front/c/3/c3f1f41e-98fc-4f6b-b287-c8899dff8ab0.png?1562563557', $model->image_uri_png);
        $this->assertEquals(96, $model->collector_number);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_can_import_a_card_with_a_huge_cmc()
    {
        // CMC 1.000.000
        $cardmarket_id = 14884;
        $skryfall_card_id = '77fe1662-7927-4909-8d25-6924e6fc27eb';

        $response = $this->mockFindByCardmarketId($cardmarket_id, $skryfall_card_id);

        $model = Card::findByCardmarketId($cardmarket_id);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals($skryfall_card_id, $model->id);
        $this->assertEquals($response['cmc'], $model->cmc);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_has_an_image_if_the_card_has_two_faces()
    {
        $cardmarket_id = 496410;
        $skryfall_card_id = '609d3ecf-f88d-4268-a8d3-4bf2bcf5df60';

        $this->mockFindByCardmarketId($cardmarket_id, $skryfall_card_id);

        $model = Card::findByCardmarketId($cardmarket_id);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals($skryfall_card_id, $model->id);
        $this->assertEquals('B', $model->color_order_by);
        $this->assertEquals('https://cards.scryfall.io/small/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.jpg?1604195984', $model->image_uri_small);
        $this->assertEquals('https://cards.scryfall.io/normal/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.jpg?1604195984', $model->image_uri_normal);
        $this->assertEquals('https://cards.scryfall.io/large/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.jpg?1604195984', $model->image_uri_large);
        $this->assertEquals('https://cards.scryfall.io/png/front/6/0/609d3ecf-f88d-4268-a8d3-4bf2bcf5df60.png?1604195984', $model->image_uri_png);
        $this->assertEquals(111, $model->collector_number);
    }

    /**
     * @test
     */
    public function it_has_an_image_if_the_card_has_two_faces_on_the_front()
    {
        $cardmarket_id = 497835;
        $skryfall_card_id = '531d60ad-39f6-4d79-b276-ec70636b123b';

        $this->mockFindByCardmarketId($cardmarket_id, $skryfall_card_id);

        $model = Card::findByCardmarketId(497835);
        $this->assertInstanceOf(Card::class, $model);
        $this->assertEquals($skryfall_card_id, $model->id);
        $this->assertEquals('M', $model->color_order_by);
        $this->assertEquals('https://cards.scryfall.io/small/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.jpg?1599766870', $model->image_uri_small);
        $this->assertEquals('https://cards.scryfall.io/normal/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.jpg?1599766870', $model->image_uri_normal);
        $this->assertEquals('https://cards.scryfall.io/large/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.jpg?1599766870', $model->image_uri_large);
        $this->assertEquals('https://cards.scryfall.io/png/front/5/3/531d60ad-39f6-4d79-b276-ec70636b123b.png?1599766870', $model->image_uri_png);
        $this->assertEquals(239, $model->collector_number);
    }

    /**
     * @test
     */
    public function it_returns_null_if_not_found()
    {
        $set_code = 'mmq';
        $card_number = 99999;

        $card_mock = Mockery::mock('overload:' . \Cardmonitor\Skryfall\Card::class);
        $card_mock->shouldReceive('findByCodeAndNumber')
            ->withArgs([
                $set_code,
                $card_number
            ])
            ->andThrow(new \GuzzleHttp\Exception\ClientException(
                'No card found with the given ID or set code',
                new \GuzzleHttp\Psr7\Request('GET', 'https://api.scryfall.com/cards/mmqs/99999'),
                new \GuzzleHttp\Psr7\Response(Response::HTTP_NOT_FOUND)
            )
        );

        $model = Card::findByCodeAndNumber($set_code, $card_number);
        $this->assertNull($model);

        Mockery::close();
    }

    /**
     * @test
     */
    public function it_gets_colors_string_attribute()
    {
        $model = new Card();
        $model->colors = ['b', 'w'];
        $this->assertEquals('b, w', $model->colors_string);
    }

    /**
     * @test
     */
    public function it_gets_color_identity_string_attribute()
    {
        $model = new Card();
        $model->color_identity = ['b', 'w'];
        $this->assertEquals('b, w', $model->color_identity_string);
    }

    /**
     * @test
     */
    public function it_has_a_release_date()
    {
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

    private function mockFindByCardmarketId(int $cardmarket_id, string $skryfall_card_id): array
    {
        $response = json_decode(file_get_contents('tests/snapshots/skryfall/cards/' . $skryfall_card_id . '.json'), true);

        $card_mock = Mockery::mock('overload:' . \Cardmonitor\Skryfall\Card::class);
        $card_mock->shouldReceive('findByCardmarketId')
            ->with($cardmarket_id)
            ->andReturn($response);

        return $response;
    }
}
