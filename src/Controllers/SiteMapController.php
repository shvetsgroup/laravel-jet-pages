<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Builders\Outline;
use ShvetsGroup\JetPages\Page\PageRegistry;
use ShvetsGroup\JetPages\Page\PageUtils;
use Watson\Sitemap\Sitemap;
use Watson\Sitemap\Tags\MultilingualTag;

class SiteMapController extends Controller
{
    /**
     * @var PageRegistry
     */
    private $pages;

    /**
     * @var Outline
     */
    private $outline;

    /**
     * @var Sitemap
     */
    private $sitemap;

    public function __construct()
    {
        $this->pages = app('pages');
        $this->outline = app('jetpages.outline');
        $this->sitemap = app('sitemap');
    }

    /**
     * Display the sitemap.
     * @return Response
     */
    public function sitemap()
    {
        $pages = $this->pages->getPublic();

        $sitemapChangeFrequency = config('jetpages.sitemap_change_frequency', [
            'page' => 'daily'
        ]);

        $sitemapPriorities = config('jetpages.sitemap_priority', [
            'page' => 'default'
        ]);

        $localesOnThisDomain = PageUtils::getLocalesOnDomain();

        foreach ($pages as $page) {
            $pageLocale = $page->getAttribute('locale');

            if (isset($localesOnThisDomain) && !in_array($pageLocale, $localesOnThisDomain)) {
                continue;
            }

            if ($page->canonical) {
                continue;
            }

            $outline = $this->outline->getFlatOutline(null, $pageLocale);

            $slug = $page->slug;
            $absoluteUrl = $page->uri(true);
            if ($slug === 'index') {
                $priority = 1;
            } else {
                if (isset($sitemapPriorities[$page->type]) && $sitemapPriorities[$page->type] != 'default') {
                    $priority = $sitemapPriorities[$page->type];
                } else {
                    $priority = round(((isset($outline[$slug]) ? (0.5 / max(1, $outline[$slug])) : 0) + 0.5) * 100) / 100;
                }
            }

            $alternativeUris = $page->alternativeUris(true);
            if (count($alternativeUris) > 1) {
                $default_locale = config('app.default_locale');
                if (isset($alternativeUris[$default_locale])) {
                    $alternativeUris['x-default'] = $alternativeUris[$default_locale];
                    unset($alternativeUris[$default_locale]);
                }
                $this->sitemap->addTag(new MultilingualTag(
                    $absoluteUrl,
                    $page->updated_at,
                    $sitemapChangeFrequency[$page->type] ?? 'daily',
                    $priority,
                    $alternativeUris
                ));
            } else {
                $this->sitemap->addTag($absoluteUrl, $page->updated_at, 'daily', $priority);
            }
        }
        return $this->sitemap->renderSitemap();
    }
}
