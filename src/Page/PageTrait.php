<?php namespace ShvetsGroup\JetPages\Page;

trait PageTrait
{
    /**
     * Load page object by its uri.
     *
     * @param $uri
     * @return mixed
     */
    public function findByUriOrFail($uri)
    {
        $page = $this->findByUri($uri);
        if (!$page) {
            return abort(404);
        }
        return $page;
    }

    /**
     * Make sure slug is correct.
     */
    protected function checkSlug($slugAttribute = 'slug', $required = true)
    {
        $slug = $this->getAttribute($slugAttribute);

        if (!$slug) {
            if ($required) {
                throw new PageAttributeException("Page requires a slug field.");
            }
            else {
                return null;
            }
        }

        $slug = $this->uriToSlug($slug);
        $this->setAttribute($slugAttribute, $slug, true);
        return $slug;
    }

    /**
     * Sanitize uri for usage as slug.
     *
     * @param $uri
     * @return string
     */
    function uriToSlug($uri)
    {
        return $uri == '/' ? 'index' : $uri;
    }
}