<?php

namespace ShvetsGroup\JetPages\Controllers;

use EllisTheDev\Robots\Robots;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

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
     * @return Response
     */
    public function robots()
    {
        $robots_txt = $this->robots->generate();

        if (!$robots_txt) {
            $addedStar = false;

            if (strpos($robots_txt, 'sitemap.xml') === false) {
                $this->robots->addUserAgent('*');
                $addedStar = true;
                $this->robots->addSitemap(url('sitemap.xml'));
            }

            if (app()->environment() != 'production') {
                if (!$addedStar) {
                    $this->robots->addUserAgent('*');
                }
                $this->robots->addDisallow('/');
            }

            $robots_txt = $this->robots->generate();
        }

        return response($robots_txt, 200, ['Content-Type' => 'text/plain']);
    }
}
