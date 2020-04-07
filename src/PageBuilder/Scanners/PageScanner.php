<?php

namespace ShvetsGroup\JetPages\PageBuilder\Scanners;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use ShvetsGroup\JetPages\Facades\PageUtils;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use Symfony\Component\Finder\SplFileInfo;
use function ShvetsGroup\JetPages\content_path;

class PageScanner implements Scanner
{
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var PageUtils
     */
    protected $pageUtils;

    /**
     * @var PageCollection
     */
    protected $pages;

    protected $paths = [];

    protected $type = 'page';
    protected $regex = '#\.(txt|html|md)$#u';

    public function __construct($paths = [])
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
        $this->pages = new PageCollection();
        $this->pageUtils = app('page.utils');
        $this->paths = $paths;
    }

    /**
     * @return PageCollection
     */
    public function getPages(): PageCollection
    {
        return $this->pages;
    }

    /**
     * Discover all files for the future scan.
     * @return Collection
     */
    public function discoverAllFiles(): Collection
    {
        $files = new Collection();
        foreach ($this->paths as $path) {
            $files = $files->merge($this->findFiles($path));
        }
        return $files;
    }

    /**
     * Scan a file for new pages and add them to the found list.
     *
     * @param  string  $filepath
     * @param  string  $directory
     * @return PageCollection
     */
    public function scanFile($filepath, $directory = null)
    {
        if (!$directory) {
            $directory = __DIR__;
        }
        $relativePath = $this->getRelativePath($directory, $filepath);
        $file = new SplFileInfo($filepath, dirname($relativePath), $relativePath);
        $this->processFile($file);
        return $this->pages;
    }

    private function getRelativePath($base, $path)
    {
        // Detect directory separator
        $separator = substr($base, 0, 1);
        $base = array_slice(explode($separator, rtrim($base, $separator)), 1);
        $path = array_slice(explode($separator, rtrim($path, $separator)), 1);

        return implode($separator, array_slice($path, count($base)));
    }

    /**
     * Scan a directory for new pages and add them to the found list.
     *
     * @param  string  $directory
     * @return PageCollection
     */
    public function scanDirectory($directory)
    {
        $files = $this->findFiles($directory);
        $this->processFiles($files);
        return $this->pages;
    }

    /**
     * @param  string  $directory
     * @return Collection
     * @throws PageScanningException
     */
    protected function findFiles($directory)
    {
        if (!is_dir($directory)) {
            throw new PageScanningException();
        }
        $files = $this->files->allFiles($directory);
        $files = array_filter($files, function ($filename) {
            return preg_match($this->regex, $filename);
        });
        $files = array_map(function ($file) {
            $file->timestamp = max($file->getMTime(), $file->getCTime());
            $file->path = $file->getPathname();
            return $file;
        }, $files);

        $files = (new Collection($files))->keyBy('path');

        return $files;
    }

    /**
     * @param  Collection  $files
     */
    public function processFiles(Collection $files)
    {
        $files->each(function ($file) {
            $this->processFile($file);
        });
    }

    /**
     * @param  SplFileInfo  $file
     */
    protected function processFile(SplFileInfo $file)
    {
        $page = $this->getPageFromFile($file);

        if (!$page) {
            return;
        }

        if ($page instanceof PageCollection) {
            $this->pages = $this->pages->merge($page);
        } else {
            $this->pages->put($page->getAttribute('localeSlug'), $page);
        }
    }

    /**
     * @param  SplFileInfo  $file
     * @return Page|PageCollection
     */
    protected function getPageFromFile(SplFileInfo $file)
    {
        return new Page($this->getInfoFromFile($file));
    }

    /**
     * @param  SplFileInfo  $file
     * @return array
     */
    protected function getInfoFromFile(SplFileInfo $file)
    {
        $path = $file->getRelativePathname();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $localeSlug = preg_replace("/\.[^.]+$/u", "", $path);
        list($locale, $slug) = $this->pageUtils->extractLocaleFromLocaleSlug($localeSlug, true);
        $fileModificationDate = date('Y-m-d H:i:s', max($file->getMTime(), $file->getCTime()));

        return [
            'locale' => $locale,
            'slug' => $slug,
            'type' => $this->type,
            'content' => $file->getContents(),
            'scanner' => get_class($this),
            'path' => $file->getRealPath(),
            'relative_path' => preg_replace('|^'.preg_quote(content_path(), '|u').'[/]*|', '', $file->getRealPath()),
            'extension' => $extension,
            'updated_at' => $fileModificationDate,
        ];
    }
}