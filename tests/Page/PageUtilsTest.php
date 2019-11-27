<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\PageUtils;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;
use URL;

class PageUtilsTest extends AbstractTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://example.com';
        URL::forceScheme('http');
        URL::forceRootUrl($this->baseUrl);
    }

    private function freshPageUtils()
    {
        return new PageUtils();
    }

    public function setConfigToNoneLocales()
    {
        config(['laravellocalization' => []]);
        config(['sg.localeDomains' => null]);
        app()->setLocale('en');
    }

    public function setConfigToOneLocale()
    {
        config(['laravellocalization' => []]);
        config(['sg.localeDomains' => null]);
        config([
            'laravellocalization.supportedLocales' => [
                'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
            ],
        ]);
        app()->setLocale('en');
    }

    public function setConfigToMultipleLocales()
    {
        config(['laravellocalization' => []]);
        config(['sg.localeDomains' => null]);
        config([
            'laravellocalization.supportedLocales' => [
                'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
                'ru' => ['name' => 'Russian', 'script' => 'Cyrl', 'native' => 'Русский', 'regional' => 'ru_RU'],
                'zh' => ['name' => 'Chinese', 'script' => 'Hans', 'native' => '中文', 'regional' => 'zh_CN'],
            ],
        ]);
        app()->setLocale('en');
    }

    public function setConfigToMultipleLocaleDomains()
    {
        config(['laravellocalization' => []]);
        config([
            'sg.localeDomains' => [
                'en' => '',
                'ru' => '',
                'zh' => 'example.cn',
            ],
        ]);
        app()->setLocale('en');
    }

    public function testExtractLocaleFromUri()
    {
        $this->setConfigToNoneLocales();
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURI(''));
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURI('/'));
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], $this->freshPageUtils()->extractLocaleFromURI('en/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], $this->freshPageUtils()->extractLocaleFromURI('en/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('en/test'));


        $this->setConfigToMultipleLocales();
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURI(''));
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURI('/'));
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], $this->freshPageUtils()->extractLocaleFromURI('en/test'));
        $this->assertEquals(['ru', ''], $this->freshPageUtils()->extractLocaleFromURI('ru'));
        $this->assertEquals(['ru', ''], $this->freshPageUtils()->extractLocaleFromURI('ru/'));
        $this->assertEquals(['ru', 'test'], $this->freshPageUtils()->extractLocaleFromURI('ru/test'));
        $this->assertEquals(['ru', 'en/test'], $this->freshPageUtils()->extractLocaleFromURI('ru/en/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], $this->freshPageUtils()->extractLocaleFromURI('en/test'));
        $this->assertEquals(['ru', 'test'], $this->freshPageUtils()->extractLocaleFromURI('ru/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'test'], $this->freshPageUtils()->extractLocaleFromURI('en/test'));
        $this->assertEquals(['ru', 'test'], $this->freshPageUtils()->extractLocaleFromURI('ru/test'));
    }

    public function testExtractLocaleFromUrl()
    {
        $this->setConfigToNoneLocales();
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com'));
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/'));
        $this->assertEquals(['en', 'en'], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/en'));
        $this->assertEquals(['en', 'ru'], $this->freshPageUtils()->extractLocaleFromURL('http://example.cn/ru'));

        $this->setConfigToMultipleLocales();
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com'));
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/'));
        $this->assertEquals(['en', 'en'], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/en'));
        $this->assertEquals(['ru', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/ru'));
        $this->assertEquals(['ru', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.cn/ru'));

        $this->setConfigToMultipleLocaleDomains();
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com'));
        $this->assertEquals(['en', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/'));
        $this->assertEquals(['en', 'en'], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/en'));
        $this->assertEquals(['ru', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.com/ru'));
        $this->assertEquals(['zh', ''], $this->freshPageUtils()->extractLocaleFromURL('http://example.cn'));
        $this->assertEquals(['zh', 'ru'], $this->freshPageUtils()->extractLocaleFromURL('http://example.cn/ru'));
    }

    public function testAbsoluteUrl()
    {
        $this->setConfigToNoneLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals('http://example.com', $this->freshPageUtils()->absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com', $this->freshPageUtils()->absoluteUrl('/', 'en'));
        $this->assertEquals('http://example.com/test', $this->freshPageUtils()->absoluteUrl('test', 'en'));

        $this->setConfigToMultipleLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals('http://example.com', $this->freshPageUtils()->absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', $this->freshPageUtils()->absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/zh', $this->freshPageUtils()->absoluteUrl('zh', 'zh'));
        $this->assertEquals('http://example.com/test', $this->freshPageUtils()->absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', $this->freshPageUtils()->absoluteUrl('ru/test', 'ru'));

        $this->setConfigToMultipleLocaleDomains();
        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals('http://example.com', $this->freshPageUtils()->absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', $this->freshPageUtils()->absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/test', $this->freshPageUtils()->absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', $this->freshPageUtils()->absoluteUrl('ru/test', 'ru'));
        $this->assertEquals('http://example.cn', $this->freshPageUtils()->absoluteUrl('', 'zh'));
        $this->assertEquals('http://example.cn/test', $this->freshPageUtils()->absoluteUrl('test', 'zh'));

        URL::forceScheme('http');
        URL::forceRootUrl('http://example.cn');
        $this->setConfigToMultipleLocaleDomains();
        config(['app.url' => 'http://example.com']);
        $this->assertEquals('http://example.com', $this->freshPageUtils()->absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', $this->freshPageUtils()->absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/test', $this->freshPageUtils()->absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', $this->freshPageUtils()->absoluteUrl('ru/test', 'ru'));
        $this->assertEquals('http://example.cn', $this->freshPageUtils()->absoluteUrl('', 'zh'));
        $this->assertEquals('http://example.cn/test', $this->freshPageUtils()->absoluteUrl('test', 'zh'));

        URL::forceScheme('http');
        URL::forceRootUrl('http://example.cn');
        $this->setConfigToMultipleLocaleDomains();
        config([
            'sg.localeDomains' => [
                'en' => 'example.com',
                'ru' => 'example.com',
                'zh' => 'example.cn',
            ],
        ]);
        config(['app.url' => 'http://example.com']);
        $this->assertEquals('http://example.com', $this->freshPageUtils()->absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', $this->freshPageUtils()->absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/test', $this->freshPageUtils()->absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', $this->freshPageUtils()->absoluteUrl('ru/test', 'ru'));
        $this->assertEquals('http://example.cn', $this->freshPageUtils()->absoluteUrl('', 'zh'));
        $this->assertEquals('http://example.cn/test', $this->freshPageUtils()->absoluteUrl('test', 'zh'));
    }

    /**
     * @dataProvider dataDontIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured
     */
    public function testDontIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured($uri, $result)
    {
        $this->setConfigToNoneLocales();
        $this->assertEquals($result, $this->freshPageUtils()->uriToLocaleSlugArray($uri));
    }

    public function dataDontIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured()
    {
        return [
            ['/', ['en', 'index']],
            ['en', ['en', 'en']],
            ['en/test', ['en', 'en/test']],
            ['ru', ['en', 'ru']],
            ['ru/test', ['en', 'ru/test']],
        ];
    }

    /**
     * @dataProvider dataDontIncludeDefaultLocaleInUrl_WithLanguageSupportConfigured
     */
    public function testDontIncludeDefaultLocaleInUrl_WitLanguageSupportConfigured($uri, $result)
    {
        $this->setConfigToMultipleLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals($result, $this->freshPageUtils()->uriToLocaleSlugArray($uri));
    }

    public function dataDontIncludeDefaultLocaleInUrl_WithLanguageSupportConfigured()
    {
        return [
            ['/', ['en', 'index']],
            ['en', ['en', 'en']],
            ['en/test', ['en', 'en/test']],
            ['ru', ['ru', 'index']],
            ['ru/test', ['ru', 'test']],
        ];
    }

    /**
     * @dataProvider dataIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured
     */
    public function testIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured($uri, $result)
    {
        $this->setConfigToNoneLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals($result, $this->freshPageUtils()->uriToLocaleSlugArray($uri));
    }

    public function dataIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured()
    {
        return [
            ['/', ['en', 'index']],
            ['en', ['en', 'index']],
            ['en/test', ['en', 'test']],
            ['ru', ['en', 'ru']],
            ['ru/test', ['en', 'ru/test']],
        ];
    }

    /**
     * @dataProvider dataIncludeDefaultLocaleInUrl_WithLanguageSupportConfigured
     */
    public function testIncludeDefaultLocaleInUrl_WithLanguageSupportConfigured($uri, $result)
    {
        $this->setConfigToMultipleLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals($result, $this->freshPageUtils()->uriToLocaleSlugArray($uri));
    }

    public function dataIncludeDefaultLocaleInUrl_WithLanguageSupportConfigured()
    {
        return [
            ['/', ['en', 'index']],
            ['en', ['en', 'index']],
            ['en/test', ['en', 'test']],
            ['ru', ['ru', 'index']],
            ['ru/test', ['ru', 'test']],
        ];
    }

    public function testSlugToUri()
    {
        $this->assertEquals('/', $this->freshPageUtils()->slugToUri('index'));
    }

    public function testUriToSlug()
    {
        $this->assertEquals('index', $this->freshPageUtils()->uriToSlug(''));
        $this->assertEquals('index', $this->freshPageUtils()->uriToSlug('/'));
    }

    public function makeLocaleUri()
    {
        $this->assertEquals('/', $this->freshPageUtils()->makeUri(null, 'index'));
        $this->assertEquals('/', $this->freshPageUtils()->makeUri('en', 'index'));
        $this->assertEquals('ru', $this->freshPageUtils()->makeUri('ru', 'index'));
    }

    public function testBaseDir()
    {
        config(['app.url' => 'http://example.com/']);
        $this->assertEquals('http://example.com/', $this->freshPageUtils()->getBaseUrl());
        config(['app.url' => 'http://example.com']);
        $this->assertEquals('http://example.com/', $this->freshPageUtils()->getBaseUrl());

        $this->assertEquals('http://example.cn/', $this->freshPageUtils()->getBaseUrl('http://example.cn/'));
        $this->assertEquals('http://example.com/', $this->freshPageUtils()->getBaseUrl('example.cn'));
    }
}
