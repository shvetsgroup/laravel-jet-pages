<?php namespace ShvetsGroup\JetPages\Page;

class EloquentPageRegistry extends AbstractPageRegistry
{
    /**
     * Find a page by string uri.
     *
     * @param $uri
     * @return null|Page
     */
    public function findByUri($uri)
    {
        $slug = $this->uriToSlug($uri);
        $page = EloquentPage::where('slug', $slug)->first();
        return $page ?: null;
    }

    /**
     * Get the array of all page slugs.
     * @return string[]
     */
    public function index()
    {
        return app('Illuminate\Database\Connection')->table('pages')->pluck('slug');
    }

    /**
     * Get the array of all page objects.
     * @return Page[]
     */
    public function getAll()
    {
        return EloquentPage::all()->keyBy('slug')->all();
    }

    /**
     * Get the time of last page update.
     * @return int
     */
    public function lastUpdatedTime()
    {
        $last_updated = EloquentPage::orderBy('updated_at', 'DESC')->first();
        if ($last_updated) {
            $last_updated->fresh();
        }
        return $last_updated ? $last_updated->updated_at : 0;
    }
}
