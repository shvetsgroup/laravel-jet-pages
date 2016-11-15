<?php namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Builders\Outline;
use ShvetsGroup\JetPages\Page\PageRegistry;
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
     * @return \Illuminate\Http\Response
     */
    public function sitemap()
    {
        $pages = $this->pages->getAll();

        foreach ($pages as $page) {
            $outline = $this->outline->getFlatOutline(null, $page->getAttribute('locale'));

            $uri = $page->uri();
            if ($uri == '/') {
                $priority = 1;
            } else {
                $priority = round(((isset($outline[$uri]) ? (0.5 / max(1, $outline[$uri])) : 0) + 0.5) * 100) / 100;
            }

            $alternativeUris = $page->alternativeUris(true);
            if (count($alternativeUris) > 1) {
                $default_locale = config('app.default_locale');
                if (isset($alternativeUris[$default_locale])) {
                    $alternativeUris['x-default'] = $alternativeUris[$default_locale];
                    unset($alternativeUris[$default_locale]);
                }
                $this->sitemap->addTag(new MultilingualTag(
                    url($uri),
                    $page->updated_at,
                    'daily',
                    $priority,
                    $alternativeUris
                ));
            } else {
                $this->sitemap->addTag(url($uri), $page->updated_at, 'daily', $priority);
            }
        }
        return $this->sitemap->renderSitemap();
    }
}
