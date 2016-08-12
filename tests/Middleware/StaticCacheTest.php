<?php namespace ShvetsGroup\Tests\JetPages\Middleware;

use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class StaticCacheTest extends AbstractTestCase
{
    /**
     * @var PageRegistry
     */
    private $pages;

    public function setUp()
    {
        parent::setUp();
        $this->pages = app()->make('pages');
        $this->deleteDir(public_path('cache'));
    }

    public function deleteDir($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        return is_file($path) ?
            @unlink($path) :
            array_map([$this, 'deleteDir'], glob($path.'/*')) == @rmdir($path);
    }

    public function testCacheHTML()
    {
        $this->pages->createAndSave(['slug' => 'index']);
        $this->visit('/')->seeStatusCode(200);
        $this->assertFileExists(public_path('cache/index.html'));

        $this->pages->createAndSave(['slug' => 'test']);
        $this->visit('/test')->seeStatusCode(200);
        $this->assertFileExists(public_path('cache/test/index.html'));

        $this->pages->createAndSave(['slug' => 'test/test']);
        $this->visit('/test/test')->seeStatusCode(200);
        $path = public_path('cache/test/test/index.html');
        $this->assertFileExists($path);
        $this->assertStringStartsWith('<!-- Cached on ', file_get_contents($path));
    }

    public function testCacheSitemap()
    {
        $this->pages->createAndSave(['slug' => 'index']);
        $this->visit('/sitemap.xml')->seeStatusCode(200);
        $path = public_path('cache/sitemap.xml');
        $this->assertFileExists($path);
        $this->assertStringStartsNotWith('<!-- Cached on ', file_get_contents($path));
    }

    public function testCacheJSON()
    {
        $this->pages->createAndSave(['slug' => 'index']);
        $this->visit('/ajax/jetpages/timestamp.json')->seeStatusCode(200);
        $path = public_path('cache/ajax/jetpages/timestamp.json');
        $this->assertFileExists($path);
        $this->assertStringStartsNotWith('<!-- Cached on ', file_get_contents($path));
    }

    public function testNoCache()
    {
        $this->pages->createAndSave(['slug' => 'index', 'cache' => false]);
        $this->visit('/')->seeStatusCode(200);
        $this->assertFileNotExists(public_path('cache/index.html'));
    }
}
