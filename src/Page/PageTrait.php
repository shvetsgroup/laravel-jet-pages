<?php namespace ShvetsGroup\JetPages\Page;

trait PageTrait
{
    /**
     * Make sure slug is correct.
     * @param string $slugAttribute
     * @param bool $required
     * @return null|string
     * @throws PageException
     */
    protected function checkSlug($slugAttribute = 'slug', $required = true)
    {
        $slug = $this->getAttribute($slugAttribute);

        if (!$slug) {
            if ($required) {
                throw new PageException("Page requires a slug field.");
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