<?php

namespace Tests\Unit\Enums\ExternalIds;

use Tests\TestCase;
use App\Enums\ExternalIds\ExternalType;

class ExternalTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_return_the_name_of_the_external_type()
    {
        $this->assertEquals('Cardmarket', ExternalType::CARDMARKET->name());
        $this->assertEquals('WooCommerce', \App\Enums\ExternalIds\ExternalType::WOOCOMMERCE->name());
    }
}
