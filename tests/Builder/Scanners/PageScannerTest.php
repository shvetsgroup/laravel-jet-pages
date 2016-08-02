<?php namespace ShvetsGroup\Tests\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Builders\Scanners\PageScanner;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use function ShvetsGroup\JetPages\content_path;
use Symfony\Component\Finder\SplFileInfo;

class PageScannerTest extends AbstractTestCase
{
    /**
     * @var PageScanner
     */
    private $scanner;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    public function setUp()
    {
        parent::setUp();
        $this->scanner = new PageScanner();
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    public function testScan()
    {
        $pages = $this->scanner->scanDirectory(content_path('pages'));
        $this->assertEquals(4, count($pages));
    }

    public function testFindFiles()
    {
        $files = $this->scanner->findFiles(content_path('pages'));
        $this->assertEquals(4, count($files));
    }

    public function testProcessFile()
    {
        $path = content_path('pages') . '/index.md';
        $file = new SplFileInfo($path, '', 'index.md');
        $page = $this->scanner->processFile($file);
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

    /**
     * @expectedException \ShvetsGroup\JetPages\Builders\Scanners\PageScanningException
     */
    public function testFindFilesError()
    {
        $this->scanner->findFiles(content_path('pages/123'));
    }
}
