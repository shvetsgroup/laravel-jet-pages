<?php namespace ShvetsGroup\JetPages\Commands;

use Illuminate\Console\Command;
use ShvetsGroup\JetPages\Builders\BaseBuilder;

class Build extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jp:build
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
        $clear = $this->option('clear');
        $builder->build($clear);
    }
}