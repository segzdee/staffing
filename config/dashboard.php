<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Role-specific navigation items for each user type's dashboard sidebar.
    | Each item includes icon SVG path, label, route name, and active detection.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: Routes here must match actual routes defined in routes/web.php
    | Current dashboard routes use 'dashboard.*' prefix (e.g., dashboard.worker)
    | The sidebar-nav component wraps each item with Route::has() to prevent errors
    | for routes that don't exist yet.
    |
    */

    'navigation' => [
        'worker' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'dashboard.worker',
                'active' => ['dashboard.worker', 'dashboard.index'],
            ],
            [
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                'label' => 'Browse Shifts',
                'route' => 'shifts.index',
                'active' => ['shifts.index', 'shifts.show'],
            ],
            [
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'label' => 'Profile',
                'route' => 'dashboard.profile',
                'active' => ['dashboard.profile'],
            ],
            [
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Transactions',
                'route' => 'dashboard.transactions',
                'active' => ['dashboard.transactions'],
            ],
        ],

        'business' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'dashboard.company',
                'active' => ['dashboard.company', 'dashboard.index'],
            ],
            [
                'icon' => 'M12 4v16m8-8H4',
                'label' => 'Post Shift',
                'route' => 'shifts.create',
                'active' => ['shifts.create'],
            ],
            [
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                'label' => 'Browse Shifts',
                'route' => 'shifts.index',
                'active' => ['shifts.index'],
            ],
            [
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'label' => 'Profile',
                'route' => 'dashboard.profile',
                'active' => ['dashboard.profile'],
            ],
            [
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Transactions',
                'route' => 'dashboard.transactions',
                'active' => ['dashboard.transactions'],
            ],
        ],

        'agency' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'dashboard.agency',
                'active' => ['dashboard.agency', 'dashboard.index'],
            ],
            [
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                'label' => 'Browse Shifts',
                'route' => 'shifts.index',
                'active' => ['shifts.index'],
            ],
            [
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'label' => 'Profile',
                'route' => 'dashboard.profile',
                'active' => ['dashboard.profile'],
            ],
            [
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Transactions',
                'route' => 'dashboard.transactions',
                'active' => ['dashboard.transactions'],
            ],
        ],

        'admin' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'filament.admin.pages.dashboard',
                'active' => ['filament.admin.pages.dashboard'],
            ],
            [
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'label' => 'Users',
                'route' => 'filament.admin.resources.users.index',
                'active' => ['filament.admin.resources.users.index', 'filament.admin.resources.users.create', 'filament.admin.resources.users.edit'],
            ],
            [
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'label' => 'Shifts',
                'route' => 'filament.admin.resources.shifts.index',
                'active' => ['filament.admin.resources.shifts.index', 'filament.admin.resources.shifts.create', 'filament.admin.resources.shifts.edit'],
            ],
            [
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'label' => 'Profile',
                'route' => 'dashboard.profile',
                'active' => ['dashboard.profile'],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Role Display Names and Badges
    |--------------------------------------------------------------------------
    */

    'roles' => [
        'worker' => [
            'name' => 'Worker',
            'badge' => 'Shift Worker',
            'color' => 'gray-700',
        ],
        'business' => [
            'name' => 'Business',
            'badge' => 'Business Account',
            'color' => 'gray-700',
        ],
        'agency' => [
            'name' => 'Agency',
            'badge' => 'Staffing Agency',
            'color' => 'gray-700',
        ],
        'admin' => [
            'name' => 'Admin',
            'badge' => 'Platform Admin',
            'color' => 'gray-900',
        ],
    ],
];
