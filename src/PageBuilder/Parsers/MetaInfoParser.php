<?php

namespace ShvetsGroup\JetPages\PageBuilder\Parsers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ShvetsGroup\JetPages\Page\Page;
use ShvetsGroup\JetPages\Page\PageCollection;
use Symfony\Component\Yaml\Yaml;
use function ShvetsGroup\JetPages\content_path;

class MetaInfoParser implements Parser
{
    private ?Filesystem $files;

    public function __construct()
    {
        $this->files = app('Illuminate\Filesystem\Filesystem');
    }

    /**
     * @param  Page  $page
     */
    public function parse(Page $page, PageCollection $pages)
    {
         $this->setMetaFromMetaFile($page, $pages);

        $content = $page->getAttribute('content');

        if (!$content) {
            return;
        }

        $matches = [];
        if (!preg_match_all('#(^|(?<=[\n\r]))---\R#u', $content . "\n", $matches) || count($matches[0]) < 2) {
            return;
        }

        $values = preg_split('|^---\R|mu', $content . "\n", 3);
        if (isset($values[0]) && $values[0] === "") {
            array_shift($values);
        }

        if (count($values) == 1) {
            $page->setAttribute('content', trim(Arr::first($values), " \n"));
        } else {
            list($meta, $content) = $values;
            $page->setAttribute('content', trim($content, " \n"));

            $meta = Yaml::parse($meta);
            if ($meta) {
                $oldLocaleSlug = $page->localeSlug;

                foreach ($meta as $key => $value) {
                    $page->setAttribute($key, $value);
                }

                $newLocaleSlug = $page->localeSlug;
                if ($oldLocaleSlug !== $newLocaleSlug) {
                    $pages->delete($oldLocaleSlug);
                    $pages->addPage($page);
                }
            }
        }
    }

    public function setMetaFromMetaFile(Page $page, PageCollection $pages)
    {
        $allMeta = $this->getGlobalMetaArray($page->locale);

        if (!$allMeta) {
            return;
        }

        if (isset($allMeta[$page->slug])) {
            $oldLocaleSlug = $page->localeSlug;

            foreach ($allMeta[$page->slug] as $key => $value) {
                $page->setAttribute($key, $value);
            }

            $newLocaleSlug = $page->localeSlug;
            if ($oldLocaleSlug !== $newLocaleSlug) {
                $pages->delete($oldLocaleSlug);
                $pages->addPage($page);
            }
        }
    }

    public function getGlobalMetaArray($locale = 'en'): array
    {
        static $allMeta = [];
        $allMeta[$locale] = $allMeta[$locale] ?? [];
        $outlinePath = content_path("meta.yml");
        $localizedPath = Str::replaceLast('.yml', "-{$locale}.yml", $outlinePath);
        $candidates = [$localizedPath, $outlinePath];
        foreach ($candidates as $possiblePath) {
            if ($this->files->exists($possiblePath)) {
                $allMeta[$locale] = Yaml::parse($this->files->get($possiblePath));
                return $allMeta[$locale];
            }
        }
    }
}