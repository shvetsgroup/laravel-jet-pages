<?php

namespace ShvetsGroup\JetPages\PageBuilder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use function ShvetsGroup\JetPages\content_path;

class PageOutline
{
    /**
     * @var Filesystem
     */
    private $files;

    private $outline = [];

    private $flatOutline = [];

    private $path;

    private $filename;

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function getOutline($locale = 'en')
    {
        if (!isset($this->outline[$locale])) {
            $this->loadOutline($locale, null);
        }
        return $this->outline[$locale];
    }

    public function getFlatOutline($locale = 'en')
    {
        if (!isset($this->flatOutline[$locale])) {
            $outline = $this->getOutline($locale);
            $this->flatOutline[$locale] = $this->flatternOutlineRecursive($outline);
        }
        return $this->flatOutline[$locale];
    }

    public function setOutline($outline, $locale = 'en')
    {
        $this->outline[$locale] = $outline;
        return $this->outline[$locale];
    }

    public function setOutlineFromYaml($yaml, $locale = 'en')
    {
        $this->outline[$locale] = $this->getOutlineFromYaml($yaml);
        return $this->outline[$locale];
    }

    public function setOutlineFromYamlFile($file, $locale = 'en')
    {
        $this->outline[$locale] = $this->getOutlineFromYamlFile($file);
        return $this->outline[$locale];
    }

    private function flatternOutlineRecursive($a, $depth = 1)
    {
        $result = [];
        foreach ($a as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $depth;
                $result += $this->flatternOutlineRecursive($value, $depth + 1);
            } else {
                $result[$key] = $depth;
            }
        }
        return $result;
    }

    protected function loadOutline($locale = 'en', $path = null)
    {
        $this->outline[$locale] = [];

        $file = $this->getOutlineFile($locale, $path);

        if ($file) {
            $this->outline[$locale] = $this->getOutlineFromYamlFile($file);
        }

        return $this->outline[$locale];
    }

    public function getOutlineFile($locale = 'en')
    {
        if (isset($this->path)) {
            $outlinePath = $this->path;
        } else {
            $outlinePath = content_path("{$this->filename}.yml");
        }
        $localizedPath = Str::replaceLast('.yml', "-{$locale}.yml", $outlinePath);

        $candidates = [$localizedPath, $outlinePath];

        foreach ($candidates as $possiblePath) {
            if ($this->files->exists($possiblePath)) {
                return $possiblePath;
            }
        }
    }

    protected function getOutlineFromYamlFile($path)
    {
        return $this->getOutlineFromYaml($this->files->get($path));
    }

    protected function getOutlineFromYaml($yaml)
    {
        return Yaml::parse($yaml);
    }
}