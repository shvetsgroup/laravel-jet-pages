<?php

namespace ShvetsGroup\Tests\JetPages\Builders\Parsers;

use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;
use ShvetsGroup\JetPages\PageBuilder\Parsers\BreadcrumbParser;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class BreadcrumbParserTest extends AbstractTestCase
{
    /**
     * @var PageOutline
     */
    protected $outline;

    /**
     * @var BreadcrumbParser
     */
    private $parser;

    public function setUp(): void
    {
        parent::setUp();
        $this->outline = new PageOutline();
        $this->parser = new BreadcrumbParser($this->outline);
    }

    public function testDecorate()
    {
        $pages = new PageCollection();
        $index = $pages->addNewPage(['slug' => 'index', 'title' => 'index']);
        $test0 = $pages->addNewPage(['slug' => 'test0', 'title' => 'test0']);
        $test1 = $pages->addNewPage(['slug' => 'test1', 'title' => 'test1']);
        $test2 = $pages->addNewPage(['slug' => 'test2', 'title' => 'test2']);

        $this->outline->setOutlineFromYaml(<<<YAML
test0:
    test1:
        test2: 1
YAML
        );

        $this->parser->parse($test0, $pages);
        $this->assertEquals(null, $test0->breadcrumb);

        $this->parser->parse($test1, $pages);
        $this->assertEquals([
            ['href' => '/', 'title' => 'index'],
            ['href' => '/test0', 'title' => 'test0']
        ], $test1->breadcrumb);

        $this->parser->parse($test2, $pages);
        $this->assertEquals([
            ['href' => '/', 'title' => 'index'],
            ['href' => '/test0', 'title' => 'test0'],
            ['href' => '/test1', 'title' => 'test1']
        ], $test2->breadcrumb);
    }
}
