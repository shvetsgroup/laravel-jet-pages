<?php

namespace ShvetsGroup\JetPages\Commands;

use Illuminate\Console\Command;
use ShvetsGroup\JetPages\Facades\PageUtils;
use ShvetsGroup\JetPages\Page\PageQuery;
use ShvetsGroup\JetPages\PageBuilder\PageBuilder;
use ShvetsGroup\JetPages\PageBuilder\PageCache;

class Cache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jetpages:cache
                            {--d|cache_dir= : Override standard cache directory.}
                            {--u|base_url= : Base url for the generated cache files.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate content static cache.';

    /**
     * Execute console command.
     * @param  PageBuilder  $builder
     */
    public function handle(PageBuilder $builder)
    {
        $start_time = microtime(true);
        if ($cache_dir = $this->option('cache_dir')) {
            config(['jetpages.static_cache_public_directory' => $cache_dir]);
        }

        if (app()->bound('laravellocalization')) {
            $localization = app('laravellocalization');
        } else {
            $localization = app();
        }

        $baseUrl = $this->option('base_url') ?? config('app.url');
        url()->forceRootUrl($baseUrl);
        $localesOnThisDomain = PageUtils::getLocalesOnDomain($baseUrl);

        $cacheBuilder = new PageCache();
        $currentLocale = app()->getLocale();
        foreach (PageQuery::get() as $page) {
            if ($localesOnThisDomain && !in_array($page->getAttribute('locale'), $localesOnThisDomain)) {
                continue;
            }
            $localization->setLocale($page->getAttribute('locale'));
            $cacheBuilder->cachePage($page);
        }
        $localization->setLocale($currentLocale);

        print('Cache has been successfully re-built in '.round(microtime(true) - $start_time, 4).'s');
    }
}