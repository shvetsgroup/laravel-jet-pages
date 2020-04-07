<?php

namespace ShvetsGroup\Tests\JetPages\PageBuilder\Scanners;

use Illuminate\Filesystem\Filesystem;
use ShvetsGroup\JetPages\PageBuilder\Scanners\PageScanner;
use ShvetsGroup\JetPages\PageBuilder\Scanners\PageScanningException;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use function ShvetsGroup\JetPages\content_path;

class PageScannerTest extends AbstractTestCase
{
    /**
     * @var PageScanner
     */
    private $scanner;

    /**
     * @var Filesystem
     */
    private $files;

    public function setUp(): void
    {
        parent::setUp();
        $this->linkFixtureContent();
        $this->scanner = new PageScanner([content_path('pages')]);
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    public function testScan2()
    {
        $pages = $this->scanner->discoverAllFiles();
        $this->assertEquals(4, $pages->count());
        $page = $pages->first();
        $this->assertEquals($page->getCTime(), $page->timestamp);
    }

    public function testScan()
    {
        $pages = $this->scanner->scanDirectory(content_path('pages'));
        $this->assertEquals(4, $pages->count());
    }

    public function testProcessFile()
    {
        $path = content_path('pages').'/index.md';
        $pages = $this->scanner->scanFile($path, content_path('pages'));
        $page = $pages->first();

        $this->assertSame($page, $pages->get('en/index'));
        $this->assertEquals('index', $page->getAttribute('slug'));
        $this->assertEquals('page', $page->getAttribute('type'));
        $this->assertEquals(realpath($path), $page->getAttribute('path'));
        $this->assertEquals(<<<SRC
---
title: "Test md"
---
Some **test** <i>content</i>.
SRC
            , $page->getAttribute('content'));
    }

    public function testFindFilesError()
    {
        $this->expectException(PageScanningException::class);
        $this->scanner->scanDirectory(content_path('pages/123'));
    }
}
