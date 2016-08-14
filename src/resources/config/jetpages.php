<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Page Data Driver
    |--------------------------------------------------------------------------
    |
    | Page data could be stored in cache or in database table. First option will
    | work out of the box, but have a downside: on any cache reset, you will need
    | to rebuild the content. Storing in database does not have this downside,
    | but it will require a database migration to be run.
    |
    */
    'driver' => 'cache',

    /*
    |--------------------------------------------------------------------------
    | Content Root
    |--------------------------------------------------------------------------
    |
    | Path to the folder where all content lives. All content scanners and
    | includes will take it as a base path and search for content only within
    | this folder.
    |
    */
    'content_root' => resource_path('content'),

    /*
    |--------------------------------------------------------------------------
    | Content scanners
    |--------------------------------------------------------------------------
    |
    | Associative array of page scanner => [target directories within
    | content base path]. If element's key is not defined, that means that
    | a default page scanner will be used.
    |
    */
    'content_scanners' => [
        'pages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content parsers
    |--------------------------------------------------------------------------
    |
    |
    */
    'content_parsers' => [
        '\ShvetsGroup\JetPages\Builders\Parsers\MetaInfoParser',
        '\ShvetsGroup\JetPages\Builders\Parsers\NavigationParser',
        '\ShvetsGroup\JetPages\Builders\Parsers\BreadcrumbParser',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content renderers
    |--------------------------------------------------------------------------
    |
    |
    */
    'content_renderers' => [
        '\ShvetsGroup\JetPages\Builders\Renderers\IncludeRenderer',
        '\ShvetsGroup\JetPages\Builders\Renderers\MarkdownRenderer',
        '\ShvetsGroup\JetPages\Builders\Renderers\EscapePreTagRenderer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content post processors
    |--------------------------------------------------------------------------
    |
    |
    */
    'content_post_processors' => [
        '\ShvetsGroup\JetPages\Builders\PostProcessors\MenuPostProcessor',
        '\ShvetsGroup\JetPages\Builders\PostProcessors\RedirectsPostProcessor',
        '\ShvetsGroup\JetPages\Builders\PostProcessors\StaticCachePostProcessor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rebuild Pages on Page View
    |--------------------------------------------------------------------------
    |
    | Useful to instantly see changes in pages.
    |
    */
    'rebuild_page_on_view' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Is default locale present in URL
    |--------------------------------------------------------------------------
    |
    | Whether or not the default locale is added to URLs. By default, we assume
    | that default locale is not in the url and only other locales are prefixed
    | to page addresses.
    |
    | For example (assuming en is default locale):
    | - http://example.com/some-page (is address for en/some-page.md)
    | - http://example.com/ru/some-page (is address for ru/some-page.md)
    |
    */
    'default_locale_in_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Additional view providers
    |--------------------------------------------------------------------------
    |
    | Some packages may ship own view templates to render pages. In this case,
    | put the package's view namespace into this array. Say, you want to render
    | a page with type 'mypage'. In this case, JetPages will pick the first
    | mypage.blade.php in following locations:
    | 1. App's views directory (resources/views).
    | 2. Views from extra view providers.
    | 3. JetPages fallback views.
    |
    */
    'extra_view_providers' => [],
];