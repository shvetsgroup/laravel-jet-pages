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
    protected $signature = 'jetpages:cache';

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

        $files = app('files');
        $cacheDir = public_path(config('jetpages.static_cache_public_dir', 'cache'));
        if ($files->exists($cacheDir)) {
            $files->deleteDirectory($cacheDir);
        }

        if (app()->bound('laravellocalization')) {
            $localization = app('laravellocalization');
        } else {
            $localization = app();
        }

        $baseUrl = config('app.url');
        url()->forceRootUrl($baseUrl);

        $cacheBuilder = new PageCache();
        $currentLocale = app()->getLocale();
        foreach (PageQuery::get() as $page) {
            $localization->setLocale($page->getAttribute('locale'));
            $cacheBuilder->cachePage($page, true);
        }
        $localization->setLocale($currentLocale);

        print('Cache has been successfully re-built in '.round(microtime(true) - $start_time, 4).'s');
    }
}