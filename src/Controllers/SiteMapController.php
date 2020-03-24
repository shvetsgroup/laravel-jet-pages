<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Builders\Outline;
use ShvetsGroup\JetPages\Facades\PageUtils;
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
     * @return Response
     */
    public function sitemap()
    {
        $pages = $this->pages->getPublic();

        $sitemapChangeFrequency = config('jetpages.sitemap_change_frequency', [
            'page' => 'daily',
        ]);

        $sitemapPriorities = config('jetpages.sitemap_priority', [
            'page' => 'default',
        ]);

        $sitemapOverrides = config('jetpages.sitemap_overrides', []);

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

            $values = [
                'priority' => 0,
                'updated_at' => $page->updated_at,
                'frequency' => $sitemapChangeFrequency[$page->type] ?? 'daily',
                'alternativeUris' => $page->alternativeUris(true),
            ];

            if ($slug === 'index') {
                $values['priority'] = 1;
            } else {
                if (isset($sitemapPriorities[$page->type]) && $sitemapPriorities[$page->type] != 'default') {
                    $values['priority'] = $sitemapPriorities[$page->type];
                } else {
                    $values['priority'] = round(((isset($outline[$slug]) ? (0.5 / max(1, $outline[$slug])) : 0) + 0.5) * 100) / 100;
                }
            }

            if (count($values['alternativeUris']) > 1) {
                $default_locale = config('app.default_locale');
                if (isset($values['alternativeUris'][$default_locale])) {
                    $values['alternativeUris']['x-default'] = $values['alternativeUris'][$default_locale];
                    unset($values['alternativeUris'][$default_locale]);
                }
            }

            foreach ($sitemapOverrides as $rule) {
                $result = false;
                if (isset($rule['conditions'])) {
                    $result = true;
                    foreach ($rule['conditions'] as $ruleConditionField => $ruleConditionValue) {
                        $result &= $page->$ruleConditionField == $ruleConditionValue;
                    }
                }
                if ($result && isset($rule['overrides'])) {
                    foreach ($rule['overrides'] as $ruleOverrideField => $ruleOverrideValue) {
                        $values[$ruleOverrideField] = $ruleOverrideValue;
                    }
                }
            }

            if (count($values['alternativeUris']) > 1) {
                $this->sitemap->addTag(new MultilingualTag(
                    $absoluteUrl,
                    $values['updated_at'],
                    $values['frequency'],
                    $values['priority'],
                    $values['alternativeUris']
                ));
            } else {
                $this->sitemap->addTag($absoluteUrl, $values['updated_at'], $values['frequency'], $values['priority']);
            }
        }
        return $this->sitemap->renderSitemap();
    }
}
