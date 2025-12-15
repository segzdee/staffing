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

    'navigation' => [
        'worker' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'active' => ['dashboard'],
            ],
            [
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                'label' => 'Browse Shifts',
                'route' => 'shifts.index',
                'active' => ['shifts.index', 'shifts.show'],
            ],
            [
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'label' => 'My Shifts',
                'route' => 'worker.assignments',
                'active' => ['worker.assignments', 'worker.assignments.show'],
            ],
            [
                'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                'label' => 'Calendar',
                'route' => 'worker.calendar',
                'active' => ['worker.calendar', 'worker.availability'],
            ],
            [
                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'label' => 'Profile',
                'route' => 'worker.profile',
                'active' => ['worker.profile'],
            ],
            [
                'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                'label' => 'Portfolio',
                'route' => 'worker.portfolio.index',
                'active' => ['worker.portfolio.index', 'worker.portfolio.create', 'worker.portfolio.edit', 'worker.profile.featured'],
            ],
        ],

        'business' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'active' => ['dashboard'],
            ],
            [
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'label' => 'My Shifts',
                'route' => 'business.shifts.index',
                'active' => ['business.shifts.index', 'business.shifts.show'],
            ],
            [
                'icon' => 'M12 4v16m8-8H4',
                'label' => 'Post Shift',
                'route' => 'shifts.create',
                'active' => ['shifts.create'],
            ],
            [
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'label' => 'Find Workers',
                'route' => 'business.available-workers',
                'active' => ['business.available-workers'],
            ],
            [
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'label' => 'Analytics',
                'route' => 'business.analytics',
                'active' => ['business.analytics'],
            ],
        ],

        'agency' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'active' => ['dashboard'],
            ],
            [
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'label' => 'Workers',
                'route' => 'agency.workers.index',
                'active' => ['agency.workers.index', 'agency.workers.add', 'agency.workers.show'],
            ],
            [
                'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                'label' => 'Assignments',
                'route' => 'agency.assignments',
                'active' => ['agency.assignments'],
            ],
            [
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                'label' => 'Browse Shifts',
                'route' => 'agency.shifts.browse',
                'active' => ['agency.shifts.browse'],
            ],
            [
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Earnings',
                'route' => 'agency.commissions',
                'active' => ['agency.commissions'],
            ],
        ],

        'admin' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
            ],
            [
                'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                'label' => 'Users',
                'route' => 'admin.users',
                'active' => ['admin.users'],
            ],
            [
                'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                'label' => 'Shifts',
                'route' => 'admin.shifts.index',
                'active' => ['admin.shifts.index'],
            ],
            [
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'label' => 'Verifications',
                'route' => 'admin.verifications',
                'active' => ['admin.verifications'],
                'badge' => 'pending_verifications',
            ],
            [
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'label' => 'Disputes',
                'route' => 'admin.disputes',
                'active' => ['admin.disputes'],
            ],
            [
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Payments',
                'route' => 'admin.payments',
                'active' => ['admin.payments'],
            ],
            [
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'label' => 'Configuration',
                'route' => 'admin.configuration.index',
                'active' => ['admin.configuration.index', 'admin.configuration.history'],
            ],
            [
                'icon' => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'System Health',
                'route' => 'admin.system-health.index',
                'active' => ['admin.system-health.index', 'admin.system-health.incidents'],
            ],
        ],

        'ai_agent' => [
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'agent.dashboard',
                'active' => ['agent.dashboard'],
            ],
            [
                'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                'label' => 'API Documentation',
                'route' => 'agent.docs',
                'active' => ['agent.docs'],
            ],
            [
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'label' => 'API Logs',
                'route' => 'agent.logs',
                'active' => ['agent.logs'],
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
        'ai_agent' => [
            'name' => 'AI Agent',
            'badge' => 'API Access',
            'color' => 'gray-700',
        ],
    ],
];
