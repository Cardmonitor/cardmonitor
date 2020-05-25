<?php

namespace Tests\Unit\Models;

use App\Models\Localizations\Language;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_get_a_model_by_code()
    {
        $code = 'gb';
        $language = Language::getByCode($code);
        $this->assertEquals(Language::DEFAULT_ID, $language->id);
        $this->assertTrue(Cache::has('language.' . $code));

        $language = Language::getByCode('GB');
        $this->assertEquals(Language::DEFAULT_ID, $language->id);
    }
}
