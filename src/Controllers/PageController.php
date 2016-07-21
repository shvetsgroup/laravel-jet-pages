<?php namespace ShvetsGroup\JetPages\Controllers;

use App\Http\Controllers\Controller;
use ShvetsGroup\JetPages\Page\Pageable;

class PageController extends Controller
{
    /**
     * Display the specified resource.
     * @param string $uri
     * @param Pageable $pages
     * @return \Illuminate\Http\Response
     */
    public function show($uri = '/', Pageable $pages)
    {
        $page = $pages->findByUriOrFail($uri);
        return response()->view('sg/jetpages::page', $page->toArray());
    }

    /**
     * This timestamp can be used to invalidate local client content cache.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getContentTimestamp(Pageable $pages)
    {
        return response()->json([
            'timestamp' => $pages->lastUpdatedTime()
        ]);
    }
}
