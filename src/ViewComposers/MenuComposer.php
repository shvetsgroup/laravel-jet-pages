<?php namespace ShvetsGroup\JetPages\ViewComposers;

use Illuminate\View\View;
use Cache;

class MenuComposer
{

    /**
     * Bind data to the view.
     *
     * @param  View $view
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
        if (!$view->offsetExists('uri')) {
            $uri = '/';
        }
        else {
            $uri = $view->offsetGet('uri');
        }

        $locale = $view->offsetGet('locale');
        $menu = Cache::get('menu:' . $locale);

        $this->set_active_trail($menu, $uri);
        $view->offsetSet('menu', $menu);
    }

    public function set_active_trail(&$menu, $permalink)
    {
        if (isset($menu['children'])) {
            foreach ($menu['children'] as $key => &$child) {
                if ($child['uri'] == $permalink) {
                    $child['class'] = $child['class'] ?? '';
                    $child['class'] .= ' trail active';
                    return true;
                }
                if (isset($child['sub_menu'])) {
                    if ($this->set_active_trail($child['sub_menu'], $permalink)) {
                        $child['class'] = $child['class'] ?? '';
                        $child['class'] .= ' trail';
                        return true;
                    }
                }
            }
        }

        return false;
    }
}