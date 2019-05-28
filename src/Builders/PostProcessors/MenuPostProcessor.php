<?php namespace ShvetsGroup\JetPages\Builders\PostProcessors;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use function \ShvetsGroup\JetPages\content_path;

class MenuPostProcessor implements PostProcessor
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Contracts\Cache\Store
     */
    private $cache;

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->cache = app('cache.store');
    }

    /**
     * @param Page[] $updatedPages
     * @param PageRegistry $registry
     */
    public function postProcess(array $updatedPages, PageRegistry $registry)
    {
        $path = content_path('menu.yml');
        if (!$this->files->exists($path)) {
            return;
        }

        $default_outline = app('jetpages.outline')->getRawOutline($path);
        if (!$default_outline) {
            return;
        }

        $locales = config('laravellocalization.supportedLocales') ?: [config('app.default_locale') => []];
        foreach ($locales as $locale => $data) {
            $outline = [];
            $path = content_path("menu-$locale.yml");
            if ($this->files->exists($path)) {
                $outline = app('jetpages.outline')->getRawOutline($path);
            }
            if (!$outline) {
                $outline = $default_outline;
            }
            $menu = [
                'class' => 'menu-list',
                'children' => []
            ];
            foreach ($outline as $uri => $tree) {
                $menu['children'][$uri] = $this->build_toc_recursive($registry, $tree, $locale, $uri);
            }
            $this->cache->forever('menu:' . $locale, $menu);
        }
    }

    protected function build_toc_recursive(PageRegistry $registry, $menu_item, $locale, $uri)
    {
        if (strlen($uri) > 0 && ($uri[0] == '/' || preg_match('#^https?://#', $uri))) {
            $result = ['href' => $uri];
        }
        else {
            $result = [
                'href' => Page::makeLocaleUri($locale, Page::uriToSlug($uri))
            ];
            $page = $registry->findBySlug($locale, Page::uriToSlug($uri));

            if ($page) {
                $result['title'] = $page->getAttribute('title_short') ?: $page->getAttribute('title');
                $result['href'] = $page->uri(true, true);
            }
        }

        if (is_array($menu_item)) {
            if (isset($menu_item['_title'])) {
                if (starts_with($menu_item['_title'], 'trans:')) {
                    $result['title'] = trans($menu_item['_title'], [], $locale);
                }
                else {
                    $result['title'] = $menu_item['_title'];
                }
            }
            if (isset($menu_item['_icon'])) {
                $result['icon'] = $menu_item['_icon'];
            }
            if (isset($menu_item['_class'])) {
                $result['class'] = $menu_item['_class'];
            }
            if (isset($menu_item['_fragment'])) {
                $result['fragment'] = $menu_item['_fragment'];
            }
            $sub_menu = [];
            foreach ($menu_item as $key => $data) {
                if (substr($key, 0, 1) != '_') {
                    $sub_menu[$key] = $this->build_toc_recursive($registry, $data, $locale, $key);
                }
            }
            if ($sub_menu) {
                $result['sub_menu'] = [
                    'class' => '',
                    'children' => $sub_menu
                ];
            }
        }

        return $result;
    }
}
