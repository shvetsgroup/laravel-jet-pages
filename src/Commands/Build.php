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
    protected $signature = 'jetpages:build
                            {--r|reset : Clear all generated data and re-import from scratch.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build content structure.';

    /**
     * Execute console command.
     * @param  BaseBuilder  $builder
     */
    public function handle(BaseBuilder $builder)
    {
        $start_time = microtime(true);
        $reset = $this->option('reset');
        $builder->build($reset);
        print('Content has been successfully re-built in '.round(microtime(true) - $start_time, 4).'s');
    }
}