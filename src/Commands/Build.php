<?php

namespace ShvetsGroup\JetPages\Commands;

use Illuminate\Console\Command;
use ShvetsGroup\JetPages\Builders\BaseBuilder;

class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jetpages
                            {--d|cache_dir= : Override standard cache directory.}
                            {--c|clear : Clear all generated data and re-import from scratch.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build content cache.';

    /**
     * Execute console command.
     * @param BaseBuilder $builder
     */
    public function handle(BaseBuilder $builder)
    {
        $start_time = microtime(true);
        $clear = $this->option('clear');
        if ($cache_dir = $this->option('cache_dir')) {
            config(['jetpages.cache_dir' => $cache_dir]);
        }
        $builder->build($clear);
        print('Content has been successfully re-built in ' . round(microtime(true) - $start_time, 4) . 's');
    }
}