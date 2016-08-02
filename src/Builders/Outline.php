<?php namespace ShvetsGroup\JetPages\Builders;

use Symfony\Component\Yaml\Yaml;
use function ShvetsGroup\JetPages\content_path;

class Outline
{
    private $flat_outline = [];

    public function getRawOutline($path = null)
    {
        $files = app('Illuminate\Filesystem\Filesystem');
        $path = $path ?: content_path('outline.yml');
        if ($files->exists($path)) {
            return Yaml::parse($files->get($path));
        }
        return null;
    }

    public function getFlatOutline($outline_raw = null)
    {
        if ($this->flat_outline) {
            return $this->flat_outline;
        }

        $outline_raw = $outline_raw ?: $this->getRawOutline();
        if (!$outline_raw) {
            return $this->flat_outline;
        }

        $this->flat_outline = $this->walkOutlineRecursive($outline_raw);

        return $this->flat_outline;
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