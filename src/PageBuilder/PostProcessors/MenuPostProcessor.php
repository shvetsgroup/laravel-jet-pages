<?php

namespace ShvetsGroup\JetPages\PageBuilder\PostProcessors;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ShvetsGroup\JetPages\Facades\PageUtils;
use ShvetsGroup\JetPages\Page\PageCollection;
use ShvetsGroup\JetPages\PageBuilder\MenuItem;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;

class MenuPostProcessor implements PostProcessor
{
    const CLEAN_JSON = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;

    const MENU_CACHE_PATH = 'app/menu/menu.json';

    /**
     * @var Store
     */
    protected $cache;

    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var PageOutline
     */
    protected $outline;

    /**
     * @var PageUtils
     */
    protected $pageUtils;

    protected $menuCacheFile;

    protected $menuCacheDir;

    public function __construct()
    {
        $this->cache = app('cache.store');
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->outline = (new PageOutline())->setFilename('menu');
        $this->pageUtils = app('page.utils');
        $this->menuCacheFile = storage_path(static::MENU_CACHE_PATH);
        $this->menuCacheDir = dirname($this->menuCacheFile);
    }

    public function getMenuCacheFile($locale)
    {
        $menuCacheFile = storage_path(static::MENU_CACHE_PATH);
        return Str::replaceLast('.json', "-$locale.json", $menuCacheFile);
    }

    public function rebuildCache($locale)
    {
        $menuFile = $this->getMenuCacheFile($locale);

        if (file_exists($menuFile)) {
            $menuData = json_decode(file_get_contents($menuFile), true);
        } else {
            $menuData = [];
        }
        $menu = new MenuItem($menuData);

        $this->cache->forever('jetpages:menu:'.$locale, $menu);

        return $menu;
    }

    /**
     * @param  PageCollection  $updatedPages
     * @param  PageCollection  $pages
     */
    public function postProcess(PageCollection $updatedPages, PageCollection $pages)
    {
        $locales = config('laravellocalization.supportedLocales') ?: [config('app.default_locale') => []];

        $timestamps = $this->cache->get('jetpages:menu_timestamps') ?? [];

        foreach ($locales as $locale => $data) {
            $file = $this->outline->getOutlineFile($locale);
            $file = new \SplFileInfo($file);
            $timestamp = max($file->getCTime(), $file->getMTime());
            if (isset($timestamps[$locale]) && $timestamp <= $timestamps[$locale]) {
                continue;
            }

            $outline = $this->outline->getOutline($locale);

            $menu = $this->buildMenuRecursive($pages, $outline, $locale);

            if ($menu) {
                $menu->class = 'menu-list';
            }

            $this->cache->forever('jetpages:menu:'.$locale, $menu);

            $this->files->makeDirectory($this->menuCacheDir, 0755, true, true);
            $this->files->put($this->getMenuCacheFile($locale), json_encode($menu, static::CLEAN_JSON));

            $timestamps[$locale] = $timestamp;
        }

        $this->cache->forever('jetpages:menu_timestamps', $timestamps);
    }

    protected function buildMenuRecursive(PageCollection $pages, $outline, $locale, $href = null)
    {
        $result = new MenuItem();

        $isAbsoluteUriOrPath = strlen($href) > 0 && ($href[0] == '/' || preg_match('#^https?://#u', $href));
        if ($isAbsoluteUriOrPath) {
            $result->href = $href;
        } else {
            $page = $pages->findBySlug($locale, $this->pageUtils->uriToSlug($href));

            if ($page) {
                $result->fillFromArray($page->getTitleHrefArray());
            } else {
                $result->href = $this->pageUtils->makeUri($locale, $this->pageUtils->uriToSlug($href));
            }
        }

        if (is_array($outline)) {
            if (isset($outline['_title'])) {
                if (Str::startsWith($outline['_title'], 'trans:')) {
                    $result->title = trans($outline['_title'], [], $locale);
                } else {
                    $result->title = $outline['_title'];
                }
            }
            if (isset($outline['_icon'])) {
                $result->icon = $outline['_icon'];
            }
            if (isset($outline['_class'])) {
                $result->class = $outline['_class'];
            }
            if (isset($outline['_fragment'])) {
                $result->fragment = $outline['_fragment'];
            }
            $result->children = [];
            foreach ($outline as $key => $data) {
                if (substr($key, 0, 1) != '_') {
                    $result->children[] = $this->buildMenuRecursive($pages, $data, $locale, $key);
                }
            }
        }

        return $result;
    }
}
