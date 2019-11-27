<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use MadWeb\Robots\Robots;

class RobotsTxtController extends Controller
{
    /**
     * Display the robots.txt.
     * @return Response
     */
    public function robots(Robots $robots)
    {
        $robots->addUserAgent('*');

        if ($robots->shouldIndex()) {
            // If on the live server, serve a nice, welcoming robots.txt.
            $robots->addDisallow('/admin');
            $robots->addSitemap('sitemap.xml');
        } else {
            // If you're on any other server, tell everyone to go away.
            $robots->addDisallow('/');
        }

        return response($robots->generate(), 200, ['Content-Type' => 'text/plain']);
    }
}
