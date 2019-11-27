<?php

namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Parsers\MetaInfoParser;
use ShvetsGroup\JetPages\Builders\Parsers\Parser;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\SimplePageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class MetaInfoParserTest extends AbstractTestCase
{
    /**
     * @var Parser
     */
    private $parser;
    private $data = [];
    private $pages;

    public function setUp(): void
    {
        parent::setUp();
        $this->parser = new MetaInfoParser();
        $this->data = ['slug' => 'test', 'content' => "---\r\ntitle: Test\r\nslug: new-slug\n---\r\nContent"];
        $this->pages = new SimplePageRegistry();
    }

    public function testDecorate()
    {
        $page = new Page($this->data);
        $this->parser->parse($page, $this->pages);
        $this->assertEquals('new-slug', $page->getAttribute('slug'));
        $this->assertEquals('Test', $page->getAttribute('title'));
        $this->assertEquals('Content', $page->getAttribute('content'));
    }

    public function testEmptyMetaData()
    {
        $data = ['slug' => 'test', 'content' => "---\r\n---\nContent"];
        $page = new Page($data);
        $this->parser->parse($page, $this->pages);
        $this->assertEquals('Content', $page->getAttribute('content'));
    }

    public function testEmptySrc()
    {
        $data = ['slug' => 'test'];
        $page = new Page($data);
        $this->parser->parse($page, $this->pages);
        $this->assertNull($page->getAttribute('content'));
    }

    /**
     * @dataProvider noMetaData
     */
    public function testNoMetaData($data)
    {
        $page = new Page($data);
        $this->parser->parse($page, $this->pages);
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
