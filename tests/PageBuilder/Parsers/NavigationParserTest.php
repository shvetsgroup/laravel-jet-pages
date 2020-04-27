<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder\Parsers;

use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;
use ShvetsGroup\JetPages\PageBuilder\Parsers\NavigationParser;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class NavigationParserTest extends AbstractTestCase
{
    /**
     * @var PageOutline
     */
    protected $outline;

    /**
     * @var NavigationParser
     */
    private $parser;

    public function setUp(): void
    {
        parent::setUp();
        $this->outline = app('page.outline');
        $this->parser = new NavigationParser($this->outline);
    }

    public function testDecorate()
    {
        $index = ['test-1', 'test0', 'test1', 'test2', 'test3', 'test4'];
        $test_result = [];
        $pages = new PageCollection();
        foreach ($index as $i) {
            $test_result[$i] = ['href' => '/'.$i, 'title' => $i];
            $test_page[$i] = $pages->addNewPage(['slug' => $i, 'title' => $i]);
        }

        $this->outline->setOutlineFromYaml(<<<YAML
test0:
    test-1: 1
test1: 1
test2:
    test3: 1
    test4: 1
YAML
        );

        $this->parser->parse($test_page['test0'], $pages);
        $this->assertEquals(null, $test_page['test0']->prev);
        $this->assertEquals($test_result['test-1'], $test_page['test0']->next);
        $this->assertEquals($test_result['test0'], $test_page['test0']->parent);

        $this->parser->parse($test_page['test1'], $pages);
        $this->assertEquals(null, $test_page['test1']->prev);
        $this->assertEquals(null, $test_page['test1']->next);
        $this->assertEquals($test_result['test1'], $test_page['test1']->parent);

        $this->parser->parse($test_page['test2'], $pages);
        $this->assertEquals(null, $test_page['test2']->prev);
        $this->assertEquals($test_result['test3'], $test_page['test2']->next);
        $this->assertEquals($test_result['test2'], $test_page['test2']->parent);

        $this->parser->parse($test_page['test3'], $pages);
        $this->assertEquals($test_result['test2'], $test_page['test3']->prev);
        $this->assertEquals($test_result['test4'], $test_page['test3']->next);
        $this->assertEquals($test_result['test2'], $test_page['test3']->parent);

        $this->parser->parse($test_page['test4'], $pages);
        $this->assertEquals($test_result['test3'], $test_page['test4']->prev);
        $this->assertEquals(null, $test_page['test4']->next);
        $this->assertEquals($test_result['test2'], $test_page['test4']->parent);
    }
}
