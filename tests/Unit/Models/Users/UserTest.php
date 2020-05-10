<?php

namespace Tests\Unit\Models\Users;

use App\Models\Apis\Api;
use App\Models\Articles\Article;
use App\Models\Items\Item;
use App\Models\Orders\Order;
use App\Models\Rules\Rule;
use App\Models\Storages\Storage;
use App\Models\Users\Balance;
use App\Support\Users\CardmarketApi;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\RelationshipAssertions;

class UserTest extends TestCase
{
    use RelationshipAssertions;

    /**
     * @test
     */
    public function it_creates_an_api_after_it_is_created()
    {
        $user = factory(User::class)->create();
        $this->assertDatabaseHas('apis', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function it_sends_a_mail_when_created()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @test
     */
    public function it_knows_if_a_user_can_pay_an_amount()
    {
        $user = factory(User::class)->create([
            'id' => 2,
            'balance_in_cents' => 0,
        ]);
        $this->assertFalse($user->canPay(100));

        $user->balance_in_cents = 100;

        $this->assertTrue($user->canPay(100));
    }

    /**
     * @test
     */
    public function it_can_withdraw_its_balance()
    {
        $user = factory(User::class)->create([
            'id' => 2,
            'balance_in_cents' => 100,
        ]);

        $user->withdraw(100, 'test');

        $this->assertEquals(0, $user->fresh()->balance_in_cents);
        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'amount_in_cents' => 100,
            'type' => 'debit',
            'multiplier' => -1,
            'charge_reason' => 'test',
        ]);
    }

    /**
     * @test
     */
    public function it_has_one_api()
    {
        $model = factory(User::class)->create();

        $this->assertHasOne($model, $model->api, 'api');
    }

    /**
     * @test
     */
    public function it_has_many_articles()
    {
        $model = factory(User::class)->create();
        $related = factory(Article::class)->create([
            'user_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'articles');
    }

    /**
     * @test
     */
    public function it_has_many_balances()
    {
        $model = factory(User::class)->create();
        $related = factory(Balance::class)->create([
            'user_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'balances');
    }

    /**
     * @test
     */
    public function it_has_many_items()
    {
        $model = factory(User::class)->create();
        $related = factory(Item::class)->create([
            'user_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'items', 5);
    }

    /**
     * @test
     */
    public function it_has_many_orders()
    {
        $model = factory(User::class)->create();
        $related = factory(Order::class)->create([
            'user_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'orders');
    }

    /**
     * @test
     */
    public function it_has_many_rules()
    {
        $model = factory(User::class)->create();
        $related = factory(Rule::class)->create([
            'user_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'rules');
    }

    /**
     * @test
     */
    public function it_has_many_storages()
    {
        $model = factory(User::class)->create();
        $related = factory(Storage::class)->create([
            'user_id' => $model->id,
        ]);

        $this->assertHasMany($model, $related, 'storages');
    }

    /**
     * @test
     */
    public function it_get_its_cardmarket_api()
    {
        $model = factory(User::class)->create();
        $this->assertEquals(CardmarketApi::class, get_class($model->cardmarketApi));
    }
}
