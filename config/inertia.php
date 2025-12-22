<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures the server side rendering support of Inertia.
    | You may specify a ssr entry point, or set it to false to disable it.
    |
    */

    'ssr' => [
        'enabled' => env('INERTIA_SSR_ENABLED', false),
        'bundle' => env('INERTIA_SSR_BUNDLE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to locate Inertia components on the
    | filesystem. For instance, when using `assertInertia`, the assertion
    | methods will attempt to locate the component as a file relative to
    | any of the paths.
    |
    */

    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/Pages'),
        ],
        'page_extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

];
