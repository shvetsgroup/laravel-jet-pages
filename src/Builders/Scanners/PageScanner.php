<?php namespace ShvetsGroup\JetPages\Builders\Scanners;

use Illuminate\Contracts\Filesystem\Filesystem;

class PageScanner
{
    protected $files;
    protected $type = 'page';

    public function __construct(Filesystem $files){
        $this->files = $files;
    }

    public function scan($directory)
    {
        $map = [];
        $files = $this->files->allFiles($directory);
        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {
            $slug = $file->getRelativePathname();
            $slug = preg_replace('#\.(md|html)$#', '', $slug);
            $page = [
                'slug'      => $slug,
                'type'      => $this->type,
                'path'      => $file->getRealpath(),
            ];
            $map[$page['slug']] = $page;
        }
        return $map;
    }
}