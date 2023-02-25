<?php

namespace Tests\Unit\Support;

use App\Support\BackgroundTasks;
use Tests\TestCase;

class BackgroundTasksTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_set_its_content()
    {
        $BackgroundTasks = BackgroundTasks::make();
        $content = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $this->assertFileDoesNotExist(storage_path('app/background_tasks.json'));
        $this->assertEquals([], $BackgroundTasks->all());

        $BackgroundTasks->setContent($content);

        $this->assertEquals($content, $BackgroundTasks->all());
        $this->assertFileExists(storage_path('app/background_tasks.json'));

        $BackgroundTasks->setContent([]);
        $this->assertFileDoesNotExist(storage_path('app/background_tasks.json'));
    }

    /**
     * @test
     */
    public function it_can_get_a_value() {
        $BackgroundTasks = BackgroundTasks::make();
        $content = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $BackgroundTasks->setContent($content);

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals(null, $BackgroundTasks->get('baz'));
        $this->assertEquals('default', $BackgroundTasks->get('baz', 'default'));
    }

    /**
     * @test
     */
    public function it_can_put_a_value() {
        $BackgroundTasks = BackgroundTasks::make();
        $content = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $BackgroundTasks->setContent($content);

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals(null, $BackgroundTasks->get('baz'));

        $BackgroundTasks->put('baz', 'qux');

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals('qux', $BackgroundTasks->get('baz'));

        $BackgroundTasks->put('baz.foo', 'qux');

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertIsArray($BackgroundTasks->get('baz'));
        $this->assertEquals('qux', $BackgroundTasks->get('baz.foo'));

        $BackgroundTasks->put('baz.bar', 'foo');

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals('qux', $BackgroundTasks->get('baz.foo'));
        $this->assertEquals('foo', $BackgroundTasks->get('baz.bar'));

        $BackgroundTasks->put('baz:foo.bar', 'foo');

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals('qux', $BackgroundTasks->get('baz.foo'));
        $this->assertEquals('foo', $BackgroundTasks->get('baz.bar'));
        $this->assertEquals('foo', $BackgroundTasks->get('baz:foo.bar'));
    }

    /**
     * @test
     */
    public function it_can_forget_a_value() {
        $BackgroundTasks = BackgroundTasks::make();
        $content = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $BackgroundTasks->setContent($content);

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals(null, $BackgroundTasks->get('baz'));

        $BackgroundTasks->forget('foo');

        $this->assertEquals(null, $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals(null, $BackgroundTasks->get('baz'));
    }

    /**
     * @test
     */
    public function it_can_flush_its_content() {
        $BackgroundTasks = BackgroundTasks::make();
        $content = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $BackgroundTasks->setContent($content);

        $this->assertEquals('bar', $BackgroundTasks->get('foo'));
        $this->assertEquals('baz', $BackgroundTasks->get('bar'));
        $this->assertEquals(null, $BackgroundTasks->get('baz'));

        $BackgroundTasks->flush();

        $this->assertEquals([], $BackgroundTasks->all());
    }
}
