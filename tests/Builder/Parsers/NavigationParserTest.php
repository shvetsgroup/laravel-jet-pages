<?php

namespace ShvetsGroup\Tests\JetPages\Builders\Parsers;

use ShvetsGroup\JetPages\Builders\Parsers\Parser;
use ShvetsGroup\JetPages\Builders\Parsers\NavigationParser;
use ShvetsGroup\JetPages\Page\SimplePageRegistry;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class NavigationParserTest extends AbstractTestCase
{
    /**
     * @var Parser
     */
    private $parser;

    public function setUp()
    {
        parent::setUp();
        $this->parser = new NavigationParser();
    }

    public function testDecorate()
    {
        $index = ['test-1', 'test0', 'test1', 'test2', 'test3', 'test4'];
        $test_result = [];
        $pages = [];
        foreach ($index as $i) {
            $test_result[$i] = ['href' => '/' . $i, 'title' => $i];
            $pages[$i] = new Page(['slug' => $i, 'title' => $i]);
        }
        $registry = new SimplePageRegistry($pages);

        app('jetpages.outline')->getFlatOutline([
            'test0' => ['test-1' => 1],
            'test1' => 1,
            'test2' => ['test3' => 1, 'test4' => 1]
        ], 'en');

        $nav = new NavigationParser();
        foreach ($pages as $p) {
            $nav->parse($p, $registry);
        }

        $this->parser->parse($pages['test0'], $registry);
        $this->assertEquals(null, $pages['test0']->prev);
        $this->assertEquals($test_result['test-1'], $pages['test0']->next);
        $this->assertEquals($test_result['test0'], $pages['test0']->parent);

        $this->parser->parse($pages['test1'], $registry);
        $this->assertEquals(null, $pages['test1']->prev);
        $this->assertEquals(null, $pages['test1']->next);
        $this->assertEquals($test_result['test1'], $pages['test1']->parent);

        $this->parser->parse($pages['test2'], $registry);
        $this->assertEquals(null, $pages['test2']->prev);
        $this->assertEquals($test_result['test3'], $pages['test2']->next);
        $this->assertEquals($test_result['test2'], $pages['test2']->parent);

        $this->parser->parse($pages['test3'], $registry);
        $this->assertEquals($test_result['test2'], $pages['test3']->prev);
        $this->assertEquals($test_result['test4'], $pages['test3']->next);
        $this->assertEquals($test_result['test2'], $pages['test3']->parent);

        $this->parser->parse($pages['test4'], $registry);
        $this->assertEquals($test_result['test3'], $pages['test4']->prev);
        $this->assertEquals(null, $pages['test4']->next);
        $this->assertEquals($test_result['test2'], $pages['test4']->parent);
    }
}
