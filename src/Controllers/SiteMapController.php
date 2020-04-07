<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Facades\PageUtils;
use ShvetsGroup\JetPages\Page\PageQuery;
use ShvetsGroup\JetPages\PageBuilder\PageOutline;
use Watson\Sitemap\Sitemap;
use Watson\Sitemap\Tags\MultilingualTag;

class SiteMapController extends Controller
{
    /**
     * @var PageOutline
     */
    private $outline;

    /**
     * @var Sitemap
     */
    private $sitemap;

    private $sitemapChangeFrequency;

    private $sitemapPriorities;

    private $sitemapOverrides;

    private $localesOnThisDomain;

    public function __construct()
    {
        $this->outline = app('page.outline');
        $this->sitemap = app('sitemap');

        $this->sitemapChangeFrequency = config('jetpages.sitemap_change_frequency', [
            'page' => 'daily',
        ]);

        $this->sitemapPriorities = config('jetpages.sitemap_priority', [
            'page' => 'default',
        ]);

        $this->sitemapOverrides = config('jetpages.sitemap_overrides', []);

        $this->localesOnThisDomain = PageUtils::getLocalesOnDomain();
    }

    /**
     * Display the sitemap.
     * @return Response
     */
    public function sitemap()
    {
        PageQuery::where('private', false)->chunk(100, function ($pages) {
            foreach ($pages as $page) {
                $locale = $page->getAttribute('locale');
                $slug = $page->getAttribute('slug');

                if (isset($this->localesOnThisDomain) && !in_array($locale, $this->localesOnThisDomain)) {
                    continue;
                }

                if ($page->canonical) {
                    continue;
                }

                $outline = $this->outline->getFlatOutline($locale);

                $absoluteUrl = $page->url;

                $values = [
                    'priority' => 0,
                    'updated_at' => $page->updated_at,
                    'frequency' => $this->sitemapChangeFrequency[$page->type] ?? 'daily',
                    'alternativeUris' => $page->alternativeUris(true),
                ];

                if ($slug === 'index') {
                    $values['priority'] = 1;
                } else {
                    if (isset($this->sitemapPriorities[$page->type]) && $this->sitemapPriorities[$page->type] != 'default') {
                        $values['priority'] = $this->sitemapPriorities[$page->type];
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

                foreach ($this->sitemapOverrides as $rule) {
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
        });

        return $this->sitemap->renderSitemap();
    }
}
