<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder\Parsers;

use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\Parsers\MetaInfoParser;
use ShvetsGroup\JetPages\PageBuilder\Parsers\Parser;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MetaInfoParserTest extends AbstractTestCase
{
    /**
     * @var Parser
     */
    private $parser;
    private $testAttributes = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = new MetaInfoParser();
        $this->testAttributes = ['slug' => 'test', 'content' => "---\r\ntitle: Test\r\nslug: new-slug\n---\r\nContent"];
    }

    public function testDecorate()
    {
        $pages = new PageCollection();
        $page = $pages->addNewPage($this->testAttributes);
        $this->parser->parse($page, $pages);
        $this->assertEquals('new-slug', $page->getAttribute('slug'));
        $this->assertEquals('Test', $page->getAttribute('title'));
        $this->assertEquals('Content', $page->getAttribute('content'));
    }

    public function testEmptyMetaData()
    {
        $data = ['slug' => 'test', 'content' => "---\r\n---\nContent"];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->parser->parse($page, $pages);
        $this->assertEquals('Content', $page->getAttribute('content'));
    }

    public function testEmptySrc()
    {
        $data = ['slug' => 'test'];
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->parser->parse($page, $pages);
        $this->assertNull($page->getAttribute('content'));
    }

    /**
     * @dataProvider noMetaData
     */
    public function testNoMetaData($data)
    {
        $pages = new PageCollection();
        $page = $pages->addNewPage($data);
        $this->parser->parse($page, $pages);
        $this->assertEquals($data['content'], $page->getAttribute('content'));
    }

    public function noMetaData()
    {
        return [
            [['slug' => 'test', 'content' => "Content"]],
            [['slug' => 'test', 'content' => "---Content"]],
            [['slug' => 'test', 'content' => "---\r\n---Content"]],
        ];
    }
}
