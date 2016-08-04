<?php namespace ShvetsGroup\JetPages\Builders\Scanners;

use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageRegistry;
use Symfony\Component\Finder\SplFileInfo;

class PageScanner implements Scanner
{
    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var PageRegistry
     */
    protected $pages;

    protected $type = 'page';
    protected $regex = '#\.(txt|html|md)$#';

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->pages = app('pages');
    }

    /**
     * @param string $directory
     * @return Page[]
     */
    public function scanDirectory($directory)
    {
        $files = $this->findFiles($directory);
        return $this->processFiles($files);
    }

    /**
     * @param string $directory
     * @return array
     * @throws PageScanningException
     */
    public function findFiles($directory)
    {
        if (!is_dir($directory)) {
            throw new PageScanningException();
        }
        $files = $this->files->allFiles($directory);
        return array_filter($files, function ($filename) {
            return preg_match($this->regex, $filename);
        });
    }

    /**
     * @param array $files
     * @return array
     * @throws PageProcessingException
     */
    public function processFiles(array $files)
    {
        $map = [];
        foreach ($files as $file) {
            $map[] = $this->processFile($file);
        }
        return $map;
    }

    /**
     * @param SplFileInfo $file
     * @return Page
     */
    public function processFile(SplFileInfo $file)
    {
        $slug = $file->getRelativePathname();
        $extension = pathinfo($slug, PATHINFO_EXTENSION);
        $slug = preg_replace("/\.[^.]+$/", "", $slug);
        return $this->pages->new([
            'slug' => $slug,
            'type' => $this->type,
            'extension' => $extension,
            'path' => $file->getRealPath(),
            'content' => $file->getContents(),
            'updated_at' => $file->getMTime(),
        ]);
    }

    /**
     * @param string $filepath
     * @param string $directory
     * @return Page
     */
    public function scanFile($filepath, $directory)
    {
        $relativePath = $this->getRelativePath($directory, $filepath);
        $file = new SplFileInfo($filepath, dirname($relativePath), $relativePath);
        return $this->processFile($file);
    }

    public function getRelativePath($base, $path) {
        // Detect directory separator
        $separator = substr($base, 0, 1);
        $base = array_slice(explode($separator, rtrim($base,$separator)),1);
        $path = array_slice(explode($separator, rtrim($path,$separator)),1);

        return implode($separator, array_slice($path, count($base)));
    }
}