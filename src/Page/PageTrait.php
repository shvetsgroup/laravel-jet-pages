<?php namespace ShvetsGroup\JetPages\Page;

trait PageTrait {
    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    function uriToSlug($uri) {
        return $uri == '/' ? 'index' : $uri;
    }

    /**
     * Load page object by its uri.
     *
     * @param $uri
     * @return mixed
     */
    public function findByUriOrFail($uri) {
        $page = $this->findByUri($uri);
        if (!$page) {
            return abort(404);
        }
        return $page;
    }
}