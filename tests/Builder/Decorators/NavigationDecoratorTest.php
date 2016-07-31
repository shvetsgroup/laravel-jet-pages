<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Decorators\Decorator;
use ShvetsGroup\JetPages\Builders\Decorators\NavigationDecorator;
use ShvetsGroup\JetPages\Page\ArrayPageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class NavigationDecoratorTest extends AbstractTestCase
{
    /**
     * @var Decorator
     */
    private $decorator;

    public function setUp()
    {
        parent::setUp();
        $this->decorator = new NavigationDecorator();
    }

    public function testDecorate()
    {
        $index = ['test-1', 'test0', 'test1', 'test2', 'test3', 'test4'];
        $data = [];
        $pages = [];
        foreach ($index as $i) {
            $data[$i] = ['slug' => $i, 'title' => $i];
            $pages[$i] = app()->make('page', [$data[$i]]);
        }
        $registry = new ArrayPageRegistry($pages);

        app('outline')->getFlatOutline([
            'test0' => ['test-1' => 1],
            'test1' => 1,
            'test2' => ['test3' => 1, 'test4' => 1]
        ]);

        $this->decorator->decorate($pages['test0'], $registry);
        $this->assertEquals(null, $pages['test0']->prev);
        $this->assertEquals($data['test-1'], $pages['test0']->next);
        $this->assertEquals($data['test0'], $pages['test0']->parent);

        $this->decorator->decorate($pages['test1'], $registry);
        $this->assertEquals(null, $pages['test1']->prev);
        $this->assertEquals(null, $pages['test1']->next);
        $this->assertEquals($data['test1'], $pages['test1']->parent);

        $this->decorator->decorate($pages['test2'], $registry);
        $this->assertEquals(null, $pages['test2']->prev);
        $this->assertEquals($data['test3'], $pages['test2']->next);
        $this->assertEquals($data['test2'], $pages['test2']->parent);

        $this->decorator->decorate($pages['test3'], $registry);
        $this->assertEquals($data['test2'], $pages['test3']->prev);
        $this->assertEquals($data['test4'], $pages['test3']->next);
        $this->assertEquals($data['test2'], $pages['test3']->parent);

        $this->decorator->decorate($pages['test4'], $registry);
        $this->assertEquals($data['test3'], $pages['test4']->prev);
        $this->assertEquals(null, $pages['test4']->next);
        $this->assertEquals($data['test2'], $pages['test4']->parent);
    }
}
