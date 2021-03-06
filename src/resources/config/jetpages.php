<?php

return [

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
    'content_root' => 'resources/content',

    /*
    |--------------------------------------------------------------------------
    | Cache directory
    |--------------------------------------------------------------------------
    |
    | Directory within a public dir where the static cache will be generated.
    | You will want to configure your webserver to take static pages from there.
    |
    */
    'static_cache_public_directory' => 'cache',

    'default_cache_bag' => 'default',

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

    'start_breadcrumb_from_index_page' => true,

    'min_breadcrumb_count' => 2,

    /*
    |--------------------------------------------------------------------------
    | Content renderers
    |--------------------------------------------------------------------------
    |
    |
    */
    'content_renderers' => [
        '\ShvetsGroup\JetPages\Builders\Renderers\IncludeRenderer',
        '\ShvetsGroup\JetPages\Builders\Renderers\EscapePreTagRenderer',
        '\ShvetsGroup\JetPages\Builders\Renderers\MarkdownRenderer',
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

    'cache_markdown' => env('APP_CACHE_MARKDOWN', false),

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

    /*
    |--------------------------------------------------------------------------
    | Whether or not allow search index crawlers indexing in robots.txt
    |--------------------------------------------------------------------------
    |
    | You would want to disallow this on staging.
    |
    */
    'robots_should_index' => env('ROBOTS_SHOULD_INDEX', true),

    /*
    |--------------------------------------------------------------------------
    | Sitemap change frequency
    |--------------------------------------------------------------------------
    |
    | Define sitemap change frequency for various content types.
    |
    */
    'sitemap_change_frequency' => [
        'page' => 'daily',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap priority
    |--------------------------------------------------------------------------
    |
    | Define sitemap priority for various content types.
    |
    */
    'sitemap_priority' => [
        'page' => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap overrides
    |--------------------------------------------------------------------------
    |
    | Temporary sitemap overrides (useful when publishing new content).
    |
    */
    'sitemap_overrides' => [
        [
            'conditions' => [
            ],
            'overrides' => [
            ]
        ]
    ],

];