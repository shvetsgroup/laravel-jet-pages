<?php

namespace ShvetsGroup\JetPages\Commands;

use Illuminate\Console\Command;
use ShvetsGroup\JetPages\Builders\BaseBuilder;
use ShvetsGroup\JetPages\Builders\StaticCache;
use ShvetsGroup\JetPages\Page\Page;

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
     * @param BaseBuilder $builder
     */
    public function handle(BaseBuilder $builder)
    {
        $start_time = microtime(true);
        if ($cache_dir = $this->option('cache_dir')) {
            config(['jetpages.cache_dir' => $cache_dir]);
        }

        $baseUrl = $this->option('base_url') ?? config('app.url');
        url()->forceRootUrl($baseUrl);

        $pages = app('pages')->getAll();
        $cacheBuilder = new StaticCache();
        foreach ($pages as $page) {
            $cacheBuilder->cachePage($page);
        }

        print('Cache has been successfully re-built in ' . round(microtime(true) - $start_time, 4) . 's');
    }
}