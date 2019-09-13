<?php

namespace ShvetsGroup\JetPages\ViewComposers;

use Cache;
use Illuminate\View\View;

class MenuComposer
{

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     *
     * @return void
     */
    public function compose(View $view)
    {
        if ($view->offsetExists('menu')) {
            return;
        }
        if (!$view->offsetExists('locale')) {
            return;
        }
        if (!$view->offsetExists('href')) {
            $uri = '/';
        } else {
            $uri = $view->offsetGet('href');
        }

        $locale = $view->offsetGet('locale');

        $menuFile = storage_path('app/menu/'.$locale.'.json');
        if (file_exists($menuFile)) {
            $menu = json_decode(file_get_contents($menuFile), true);
        } else {
            $menu = [];
        }

        if (!$this->set_active_trail($menu, $uri) && $view->offsetExists('breadcrumb')) {
            $breadcrumb = $view->offsetGet('breadcrumb');
            if (is_array($breadcrumb)) {
                $uri = end($breadcrumb)['href'];
                $this->set_active_trail($menu, $uri);
            }
        }
        $view->offsetSet('menu', $menu);
    }

    public function set_active_trail(&$menu, $permalink)
    {
        if (isset($menu['children'])) {
            foreach ($menu['children'] as $key => &$child) {
                if ($child['href'] == $permalink) {
                    $child['trail'] = true;
                    $child['class'] = $child['class'] ?? '';
                    $child['class'] = trim($child['class'].' trail active');
                    return true;
                }
                if (isset($child['sub_menu'])) {
                    if ($this->set_active_trail($child['sub_menu'], $permalink)) {
                        $child['trail'] = true;
                        $child['class'] = $child['class'] ?? '';
                        $child['class'] = trim($child['class'].' trail');
                        return true;
                    }
                }
            }
        }

        return false;
    }
}