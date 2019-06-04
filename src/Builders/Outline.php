<?php

namespace ShvetsGroup\JetPages\Builders;

use Symfony\Component\Yaml\Yaml;
use function ShvetsGroup\JetPages\content_path;

class Outline
{
    private $flat_outline = [];

    public function getRawOutline($path = null, $locale = '')
    {
        $files = app('Illuminate\Filesystem\Filesystem');
        $candidates = [$path, content_path("outline-$locale.yml"), content_path('outline.yml')];
        foreach ($candidates as $possible_path) {
            if ($files->exists($possible_path)) {
                return Yaml::parse($files->get($possible_path));
            }
        }
        return null;
    }

    public function getFlatOutline($outline_raw = null, $locale = '')
    {
        if (isset($this->flat_outline[$locale])) {
            return $this->flat_outline[$locale];
        }

        $raw = $outline_raw ?: $this->getRawOutline(null, $locale);
        if (!$raw && $this->flat_outline[$locale]) {
            return $this->flat_outline[$locale];
        }

        $this->flat_outline[$locale] = $this->walkOutlineRecursive($raw);

        return $this->flat_outline[$locale];
    }

    private function walkOutlineRecursive($a, $depth = 1)
    {
        $result = [];
        foreach ($a as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $depth;
                $result += $this->walkOutlineRecursive($value, $depth + 1);
            } else {
                $result[$key] = $depth;
            }
        }
        return $result;
    }

}