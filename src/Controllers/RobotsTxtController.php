<?php

namespace ShvetsGroup\JetPages\Controllers;

use Illuminate\Routing\Controller;
use EllisTheDev\Robots\Robots;

class RobotsTxtController extends Controller
{
    /**
     * @var Robots
     */
    private $robots;

    public function __construct()
    {
        $this->robots = app('robots');
    }

    /**
     * Display the robots.txt.
     * @return \Illuminate\Http\Response
     */
    public function robots()
    {
        if (app()->environment() == 'production') {
            // If on the live server, serve a nice, welcoming robots.txt.
            $this->robots->addUserAgent('*');
            $this->robots->addSitemap(url('sitemap.xml'));
        } else {
            // If you're on any other server, tell everyone to go away.
            $this->robots->addUserAgent('*');
            $this->robots->addDisallow('/');
        }

        return response($this->robots->generate(), 200, ['Content-Type' => 'text/plain']);
    }
}
