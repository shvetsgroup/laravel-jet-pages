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
            $pages = $this->processFile($file);

            if (!$pages) {
                continue;
            }

            if (!is_array($pages)) {
                $pages = [$pages];
            }

            foreach ($pages as $page) {
                $map[$page->localeSlug()] = $page;
            }
        }
        return $map;
    }

    /**
     * @param SplFileInfo $file
     * @return array|Page
     */
    public function processFile(SplFileInfo $file)
    {
        return $this->pages->new($this->parseBasicFileInfo($file));
    }

    public function parseBasicFileInfo(SplFileInfo $file)
    {
        $path = $file->getRelativePathname();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $localeSlug = preg_replace("/\.[^.]+$/", "", $path);
        list($locale, $slug) = Page::extractLocale($localeSlug, false);
        return [
            'locale' => $locale,
            'slug' => $slug,
            'type' => $this->type,
            'extension' => $extension,
            'path' => $file->getRealPath(),
            'relative_path' => preg_replace('|^' . preg_quote(\ShvetsGroup\JetPages\content_path(), '|') . '[\/]*|', '', $file->getRealPath()),
            'content' => $file->getContents(),
            'updated_at' => date('Y-m-d H:i:s', max($file->getMTime(), $file->getCTime())),
        ];
    }

    /**
     * @param string $filepath
     * @param string $directory
     * @return array|Page
     */
    public function scanFile($filepath, $directory)
    {
        $relativePath = $this->getRelativePath($directory, $filepath);
        $file = new SplFileInfo($filepath, dirname($relativePath), $relativePath);
        return $this->processFile($file);
    }

    public function getRelativePath($base, $path)
    {
        // Detect directory separator
        $separator = substr($base, 0, 1);
        $base = array_slice(explode($separator, rtrim($base, $separator)), 1);
        $path = array_slice(explode($separator, rtrim($path, $separator)), 1);

        return implode($separator, array_slice($path, count($base)));
    }
}