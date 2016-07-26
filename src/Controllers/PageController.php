<?php namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Routing\Controller;
use ShvetsGroup\JetPages\Page\Pagelike;

class PageController extends Controller
{
    private $pages;

    /**
     * @param Pagelike $pages
     */
    public function __construct(Pagelike $pages)
    {
        $this->pages = $pages;
    }

    /**
     * Display the specified resource.
     * @param string $uri
     * @return \Illuminate\Http\Response
     */
    public function show($uri = '/')
    {
        $page = $this->pages->findByUriOrFail($uri);
        return response()->view('sg/jetpages::page', $page->toArray());
    }

    /**
     * This timestamp can be used to invalidate local client content cache.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContentTimestamp()
    {
        return response()->json([
            'timestamp' => $this->pages->lastUpdatedTime()
        ]);
    }
}
