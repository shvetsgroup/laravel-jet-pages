<?php namespace ShvetsGroup\JetPages\ViewComposers;

use Illuminate\View\View;
use ShvetsGroup\JetPages\Page\Page;

class LocaleComposer
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
        if ($view->offsetExists('locale_prefix')) {
            return;
        }

        $locale = $view->offsetExists('locale') ? $view->offsetGet('locale') : app()->getLocale();
        $locale_prefix = Page::makeLocaleUri($locale, '');
        $view->with('locale', $locale);
        $view->with('locale_prefix', $locale_prefix);
        $locales = config('laravellocalization.supportedLocales') ?: config('jetpages.supportedLocales', []);
        $view->with('locales', $locales);

        $script_variables = $view->offsetExists('script_variables') ? $view->offsetGet('script_variables') : [];
        $script_variables['locale'] = $locale;
        $script_variables['locale_prefix'] = $locale_prefix;
        $view->offsetSet('script_variables', $script_variables);
    }
}