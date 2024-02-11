<?php

namespace Tests\Unit\Enums\Orders;

use Tests\TestCase;
use App\Enums\Orders\Status;

class StatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_return_an_enum_from_a_woocommerce_slug()
    {
        $this->assertEquals(Status::BOUGHT, Status::fromWooCommerceSlug('pending'));
        $this->assertEquals(Status::PAID, Status::fromWooCommerceSlug('processing'));
        $this->assertEquals(Status::BOUGHT, Status::fromWooCommerceSlug('on-hold'));
        $this->assertEquals(Status::SENT, Status::fromWooCommerceSlug('completed'));
        $this->assertEquals(Status::CANCELLED, Status::fromWooCommerceSlug('cancelled'));
        $this->assertEquals(Status::CANCELLED, Status::fromWooCommerceSlug('refunded'));
        $this->assertEquals(Status::LOST, Status::fromWooCommerceSlug('failed'));
    }

    /**
     * @test
     */
    public function it_can_return_a_woocommerce_slug_from_an_enum()
    {
        $this->assertEquals('pending', Status::BOUGHT->woocommerceSlug());
        $this->assertEquals('processing', Status::PAID->woocommerceSlug());
        $this->assertEquals('completed', Status::SENT->woocommerceSlug());
        $this->assertEquals('cancelled', Status::CANCELLED->woocommerceSlug());
        $this->assertEquals('failed', Status::LOST->woocommerceSlug());
        $this->assertEquals('completed', Status::RECEIVED->woocommerceSlug());
        $this->assertEquals('completed', Status::EVALUATED->woocommerceSlug());
    }

    /**
     * @test
     */
    public function it_can_return_a_name_from_an_enum()
    {
        $this->assertEquals('Unbezahlt', Status::BOUGHT->name());
        $this->assertEquals('Bezahlt', Status::PAID->name());
        $this->assertEquals('Versandt', Status::SENT->name());
        $this->assertEquals('Angekommen', Status::RECEIVED->name());
        $this->assertEquals('Bewertet', Status::EVALUATED->name());
        $this->assertEquals('Nicht Angekommen', Status::LOST->name());
        $this->assertEquals('Storniert', Status::CANCELLED->name());
    }

    /**
     * @test
     */
    public function it_can_return_an_array_for_filters()
    {
        $this->assertEquals([
            Status::BOUGHT->value => Status::BOUGHT->name(),
            Status::PAID->value => Status::PAID->name(),
            Status::SENT->value => Status::SENT->name(),
            Status::RECEIVED->value => Status::RECEIVED->name(),
            Status::EVALUATED->value => Status::EVALUATED->name(),
            Status::LOST->value => Status::LOST->name(),
            Status::CANCELLED->value => Status::CANCELLED->name(),
        ], Status::filter());
    }

    /**
     * @test
     */
    public function it_can_return_an_array_for_values()
    {
        $this->assertEquals([
            Status::BOUGHT->value,
            Status::CANCELLED->value,
            Status::EVALUATED->value,
            Status::LOST->value,
            Status::PAID->value,
            Status::RECEIVED->value,
            Status::SENT->value,
        ], Status::values());
    }

    /**
     * @test
     */
    public function it_can_be_used_for_validating_state()
    {
        $this->assertEquals(Status::BOUGHT->value . ',' . Status::CANCELLED->value . ',' . Status::EVALUATED->value . ',' . Status::LOST->value . ',' . Status::PAID->value . ',' . Status::RECEIVED->value . ',' . Status::SENT->value, Status::BOUGHT->value . ',' . Status::CANCELLED->value . ',' . Status::EVALUATED->value . ',' . Status::LOST->value . ',' . Status::PAID->value . ',' . Status::RECEIVED->value . ',' . Status::SENT->value);
    }
}
