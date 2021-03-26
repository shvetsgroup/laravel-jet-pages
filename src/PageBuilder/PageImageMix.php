<?php

namespace ShvetsGroup\JetPages\PageBuilder;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PageImageMix
{
    protected ?array $mixManifest;

    protected array $imgSizePrefixes;

    protected bool $isDebug;

    protected string $baseUrl;

    public function __construct()
    {
        $this->mixManifest = cache('mix-manifest', []);
        if (! $this->mixManifest && file_exists(public_path('mix-manifest.json'))) {
            $this->mixManifest = (array) json_decode(file_get_contents(public_path('mix-manifest.json'), true)) ?? [];
        }
        $this->imgSizePrefixes = config('jetpages.img_size_prefixes', ['2x']);
        $this->isDebug = config('app.debug', false);
        $this->baseUrl = url('/');
    }

    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  null  $cache_bag
     * @return bool
     */
    public function handleRequest(Request $request, Response $response)
    {
//        if (! $this->isDebug && $this->mixManifest) {
        $content = $this->processImageUrls($response->getContent());
        $response->setContent($content);
        //  }

        return true;
    }

    public function processImageUrls($content)
    {
        $content = preg_replace_callback('#href="([^"]*?\.(ico|png|svg|css|js|json|xml|zip|pdf|epub|mobi|kfx))"#', function ($matches) {
            return 'href="'.$this->mixImgUrl($matches[1]).'"';
        }, $content);

        $content = preg_replace_callback('#src="([^"]*?)"#', function ($matches) {
            return 'src="'.$this->mixImgUrl($matches[1]).'"';
        }, $content);

        $content = preg_replace_callback('#srcset="([^"]*?)"#', function ($matches) {
            $sets = explode(',', $matches[1]);
            $resultingSets = [];
            foreach ($sets as $set) {
                if (preg_match('#\s*(.+?\.[a-z]{0,3})(?:\s+([0-9]+[wx]))?\s*#', $set, $matches)) {
                    $url = $matches[1];
                    $descriptor = $matches[2] ?? '';

                    $url = $this->mixImgUrl($url);
                    $resultingSets[$descriptor] = $url.($descriptor ? ' '.$descriptor : '');
                } else {
                    $resultingSets[] = $set;
                }
            }

            foreach ($resultingSets as $descriptor => $set) {
                if (is_numeric($descriptor)) {
                    continue;
                }
                if (! in_array($descriptor, $this->imgSizePrefixes)) {
                    unset($resultingSets[$descriptor]);
                }
            }

            return 'srcset="'.implode(',', $resultingSets).'"';
        }, $content);

        return $content;
    }

    public function mixImgUrl(string $url)
    {
        $url = trim($url);

        if (starts_with($url, url('/'))) {
            $path = parse_url($url, PHP_URL_PATH);
        } else {
            $path = $url;
        }

        if (! isset($this->mixManifest[$path])) {
            return $url;
        }

        return $this->mixManifest[$path];
    }
}