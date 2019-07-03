<?php

namespace ShvetsGroup\Tests\JetPages\Page;

use ShvetsGroup\JetPages\Page\PageUtils;
use ShvetsGroup\Tests\JetPages\AbstractTestCase;

class PageUtilsTest extends AbstractTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->baseUrl = 'http://example.com';
        \URL::forceScheme('http');
        \URL::forceRootUrl($this->baseUrl);
    }

    public function setConfigToNoneLocales()
    {
        config(['laravellocalization' => []]);
        app()->setLocale('en');
    }

    public function setConfigToOneLocale()
    {
        config(['laravellocalization' => []]);
        config([
            'laravellocalization.supportedLocales' => [
                'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB']
            ]
        ]);
        app()->setLocale('en');
    }

    public function setConfigToMultipleLocales()
    {
        config(['laravellocalization' => []]);
        config([
            'laravellocalization.supportedLocales' => [
                'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
                'ru' => ['name' => 'Russian', 'script' => 'Cyrl', 'native' => 'Русский', 'regional' => 'ru_RU'],
                'zh' => ['name' => 'Chinese', 'script' => 'Hans', 'native' => '中文', 'regional' => 'zh_CN'],
            ]
        ]);
        app()->setLocale('en');
    }

    public function setConfigToMultipleLocaleDomains() {
        config(['laravellocalization' => []]);
        config([
            'laravellocalization.localeDomains' => [
                '' => ['en', 'ru'],
                'example.cn' => 'zh',
            ]
        ]);
        app()->setLocale('en');
    }

    public function testExtractLocaleFromUri()
    {
        $this->setConfigToNoneLocales();
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURI(''));
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURI('/'));
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], PageUtils::extractLocaleFromURI('en/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], PageUtils::extractLocaleFromURI('en/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('en/test'));


        $this->setConfigToMultipleLocales();
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURI(''));
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURI('/'));
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], PageUtils::extractLocaleFromURI('en/test'));
        $this->assertEquals(['ru', ''], PageUtils::extractLocaleFromURI('ru'));
        $this->assertEquals(['ru', ''], PageUtils::extractLocaleFromURI('ru/'));
        $this->assertEquals(['ru', 'test'], PageUtils::extractLocaleFromURI('ru/test'));
        $this->assertEquals(['ru', 'en/test'], PageUtils::extractLocaleFromURI('ru/en/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'en/test'], PageUtils::extractLocaleFromURI('en/test'));
        $this->assertEquals(['ru', 'test'], PageUtils::extractLocaleFromURI('ru/test'));

        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('test'));
        $this->assertEquals(['en', 'test'], PageUtils::extractLocaleFromURI('en/test'));
        $this->assertEquals(['ru', 'test'], PageUtils::extractLocaleFromURI('ru/test'));
    }

    public function testExtractLocaleFromUrl()
    {
        $this->setConfigToNoneLocales();
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURL('http://example.com'));
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURL('http://example.com/'));
        $this->assertEquals(['en', 'en'], PageUtils::extractLocaleFromURL('http://example.com/en'));
        $this->assertEquals(['en', 'ru'], PageUtils::extractLocaleFromURL('http://example.cn/ru'));

        $this->setConfigToMultipleLocales();
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURL('http://example.com'));
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURL('http://example.com/'));
        $this->assertEquals(['en', 'en'], PageUtils::extractLocaleFromURL('http://example.com/en'));
        $this->assertEquals(['ru', ''], PageUtils::extractLocaleFromURL('http://example.com/ru'));
        $this->assertEquals(['ru', ''], PageUtils::extractLocaleFromURL('http://example.cn/ru'));

        $this->setConfigToMultipleLocaleDomains();
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURL('http://example.com'));
        $this->assertEquals(['en', ''], PageUtils::extractLocaleFromURL('http://example.com/'));
        $this->assertEquals(['en', 'en'], PageUtils::extractLocaleFromURL('http://example.com/en'));
        $this->assertEquals(['ru', ''], PageUtils::extractLocaleFromURL('http://example.com/ru'));
        $this->assertEquals(['zh', ''], PageUtils::extractLocaleFromURL('http://example.cn'));
        $this->assertEquals(['zh', 'ru'], PageUtils::extractLocaleFromURL('http://example.cn/ru'));
    }

    public function testAbsoluteUrl()
    {
        $this->setConfigToNoneLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => false]);
        $this->assertEquals('http://example.com', PageUtils::absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com', PageUtils::absoluteUrl('/', 'en'));
        $this->assertEquals('http://example.com/test', PageUtils::absoluteUrl('test', 'en'));

        $this->setConfigToMultipleLocales();
        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals('http://example.com', PageUtils::absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', PageUtils::absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/zh', PageUtils::absoluteUrl('zh', 'zh'));
        $this->assertEquals('http://example.com/test', PageUtils::absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', PageUtils::absoluteUrl('ru/test', 'ru'));

        $this->setConfigToMultipleLocaleDomains();
        config(['laravellocalization.hideDefaultLocaleInURL' => true]);
        $this->assertEquals('http://example.com', PageUtils::absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', PageUtils::absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/test', PageUtils::absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', PageUtils::absoluteUrl('ru/test', 'ru'));
        $this->assertEquals('http://example.cn', PageUtils::absoluteUrl('', 'zh'));
        $this->assertEquals('http://example.cn/test', PageUtils::absoluteUrl('test', 'zh'));

        \URL::forceScheme('http');
        \URL::forceRootUrl('http://example.cn');
        $this->setConfigToMultipleLocaleDomains();
        config(['app.url' => 'http://example.com']);
        $this->assertEquals('http://example.com', PageUtils::absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', PageUtils::absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/test', PageUtils::absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', PageUtils::absoluteUrl('ru/test', 'ru'));
        $this->assertEquals('http://example.cn', PageUtils::absoluteUrl('', 'zh'));
        $this->assertEquals('http://example.cn/test', PageUtils::absoluteUrl('test', 'zh'));

        \URL::forceScheme('http');
        \URL::forceRootUrl('http://example.cn');
        $this->setConfigToMultipleLocaleDomains();
        config([
            'laravellocalization.localeDomains' => [
                'example.com' => ['en', 'ru'],
                'example.cn' => 'zh',
            ]
        ]);
        config(['app.url' => 'http://example.com']);
        $this->assertEquals('http://example.com', PageUtils::absoluteUrl('', 'en'));
        $this->assertEquals('http://example.com/ru', PageUtils::absoluteUrl('ru', 'ru'));
        $this->assertEquals('http://example.com/test', PageUtils::absoluteUrl('test', 'en'));
        $this->assertEquals('http://example.com/ru/test', PageUtils::absoluteUrl('ru/test', 'ru'));
        $this->assertEquals('http://example.cn', PageUtils::absoluteUrl('', 'zh'));
        $this->assertEquals('http://example.cn/test', PageUtils::absoluteUrl('test', 'zh'));
    }

    /**
     * @dataProvider dataDontIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured
     */
    public function testDontIncludeDefaultLocaleInUrl_WithNoLanguageSupportConfigured($uri, $result)
    {
        $this->setConfigToNoneLocales();
        $this->assertEquals($result, PageUtils::uriToLocaleSlugArray($uri));
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
        $this->assertEquals($result, PageUtils::uriToLocaleSlugArray($uri));
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
        $this->assertEquals($result, PageUtils::uriToLocaleSlugArray($uri));
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
        $this->assertEquals($result, PageUtils::uriToLocaleSlugArray($uri));
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
        $this->assertEquals('/', PageUtils::slugToUri('index'));
    }

    public function testUriToSlug()
    {
        $this->assertEquals('index', PageUtils::uriToSlug(''));
        $this->assertEquals('index', PageUtils::uriToSlug('/'));
    }

    public function makeLocaleUri() {
        $this->assertEquals('/', PageUtils::makeUri(null, 'index'));
        $this->assertEquals('/', PageUtils::makeUri('en', 'index'));
        $this->assertEquals('ru', PageUtils::makeUri('ru', 'index'));
    }

    public function testBaseDir() {
        config(['app.url' => 'http://example.com/']);
        $this->assertEquals('http://example.com/', PageUtils::getBaseUrl());
        config(['app.url' => 'http://example.com']);
        $this->assertEquals('http://example.com/', PageUtils::getBaseUrl());

        $this->assertEquals('http://example.cn/', PageUtils::getBaseUrl('http://example.cn/'));
        $this->assertEquals('http://example.com/', PageUtils::getBaseUrl('example.cn'));
    }
}
