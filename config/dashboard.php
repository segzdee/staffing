<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dashboard Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Comprehensive role-specific navigation for each user type's dashboard.
    | Grouped items use section headers, flat items appear without headers.
    |
    */

    'navigation' => [
        /*
        |--------------------------------------------------------------------------
        | WORKER (Temporary Staff) Navigation
        |--------------------------------------------------------------------------
        */
        'worker' => [
            // Dashboard (no section header)
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'worker.dashboard',
                'active' => ['worker.dashboard'],
            ],

            // Shifts Section
            'Shifts' => [
                [
                    'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                    'label' => 'Available Shifts',
                    'route' => 'shifts.index',
                    'active' => ['shifts.index', 'shifts.show'],
                    'badge' => 'Live',
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                    'label' => 'My Applications',
                    'route' => 'worker.applications',
                    'active' => ['worker.applications'],
                    'badge' => 'pendingApplicationsCount',
                ],
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'label' => 'Upcoming Shifts',
                    'route' => 'worker.assignments.index',
                    'active' => ['worker.assignments.index', 'worker.assignments.show'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Shift History',
                    'route' => 'worker.shift-history',
                    'active' => ['worker.shift-history'],
                ],
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'label' => 'Calendar View',
                    'route' => 'worker.calendar',
                    'active' => ['worker.calendar'],
                ],
            ],

            // Earnings Section
            'Earnings' => [
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Overview',
                    'route' => 'worker.earnings',
                    'active' => ['worker.earnings'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Payments',
                    'route' => 'worker.earnings.pending',
                    'active' => ['worker.earnings.pending'],
                    'badge' => 'pendingPaymentsCount',
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'label' => 'Payment History',
                    'route' => 'worker.earnings.history',
                    'active' => ['worker.earnings.history'],
                ],
                [
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'label' => 'Withdraw Funds',
                    'route' => 'worker.withdraw',
                    'active' => ['worker.withdraw'],
                ],
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Tax Documents',
                    'route' => 'worker.tax-documents',
                    'active' => ['worker.tax-documents'],
                ],
            ],

            // Profile Section
            'Profile' => [
                [
                    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                    'label' => 'My Profile',
                    'route' => 'worker.profile',
                    'active' => ['worker.profile', 'worker.profile.edit'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                    'label' => 'Skills & Certifications',
                    'route' => 'worker.skills',
                    'active' => ['worker.skills'],
                ],
                [
                    'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
                    'label' => 'Work Preferences',
                    'route' => 'worker.preferences',
                    'active' => ['worker.preferences'],
                ],
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'label' => 'Availability Schedule',
                    'route' => 'worker.availability',
                    'active' => ['worker.availability'],
                ],
                [
                    'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'label' => 'Documents',
                    'route' => 'worker.documents',
                    'active' => ['worker.documents'],
                ],
            ],

            // Communication (flat items)
            [
                'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                'label' => 'Messages',
                'route' => 'messages.index',
                'active' => ['messages.index', 'messages.show'],
                'badge' => 'unreadMessagesCount',
            ],
            [
                'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                'label' => 'Notifications',
                'route' => 'notifications.index',
                'active' => ['notifications.index'],
                'badge' => 'unreadNotificationsCount',
            ],

            // Settings & Help (flat items)
            [
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'label' => 'Settings',
                'route' => 'settings.index',
                'active' => ['settings.index', 'settings.*'],
            ],
            [
                'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Help & Support',
                'route' => 'help.index',
                'active' => ['help.index', 'help.*'],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | BUSINESS (Venue/Hospitality) Navigation
        |--------------------------------------------------------------------------
        */
        'business' => [
            // Dashboard (no section header)
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'business.dashboard',
                'active' => ['business.dashboard'],
            ],

            // Shift Management Section
            'Shift Management' => [
                [
                    'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Post New Shift',
                    'route' => 'shifts.create',
                    'active' => ['shifts.create'],
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'label' => 'Active Listings',
                    'route' => 'business.shifts.index',
                    'active' => ['business.shifts.index', 'business.shifts.show'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Approvals',
                    'route' => 'business.shifts.pending',
                    'active' => ['business.shifts.pending'],
                    'badge' => 'pendingApprovalsCount',
                ],
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'label' => 'Upcoming Shifts',
                    'route' => 'business.shifts.upcoming',
                    'active' => ['business.shifts.upcoming'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Shift History',
                    'route' => 'business.shifts.history',
                    'active' => ['business.shifts.history'],
                ],
                [
                    'icon' => 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z',
                    'label' => 'Shift Templates',
                    'route' => 'business.shifts.templates',
                    'active' => ['business.shifts.templates'],
                ],
            ],

            // Workers Section
            'Workers' => [
                [
                    'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                    'label' => 'Browse Workers',
                    'route' => 'business.available-workers',
                    'active' => ['business.available-workers'],
                ],
                [
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'label' => 'Applicants',
                    'route' => 'business.applications',
                    'active' => ['business.applications'],
                    'badge' => 'applicantsCount',
                ],
                [
                    'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                    'label' => 'Favourite Workers',
                    'route' => 'business.workers.favourites',
                    'active' => ['business.workers.favourites'],
                ],
                [
                    'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
                    'label' => 'Blocked Workers',
                    'route' => 'business.workers.blocked',
                    'active' => ['business.workers.blocked'],
                ],
                [
                    'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                    'label' => 'Worker Reviews',
                    'route' => 'business.workers.reviews',
                    'active' => ['business.workers.reviews'],
                ],
            ],

            // Payments Section
            'Payments' => [
                [
                    'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                    'label' => 'Escrow Balance',
                    'route' => 'business.payments.escrow',
                    'active' => ['business.payments.escrow'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Payments',
                    'route' => 'business.payments.pending',
                    'active' => ['business.payments.pending'],
                    'badge' => 'pendingPaymentsCount',
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'label' => 'Payment History',
                    'route' => 'business.payments.history',
                    'active' => ['business.payments.history'],
                ],
                [
                    'icon' => 'M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Add Funds',
                    'route' => 'business.payments.add-funds',
                    'active' => ['business.payments.add-funds'],
                ],
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Invoices',
                    'route' => 'business.payments.invoices',
                    'active' => ['business.payments.invoices'],
                ],
            ],

            // Reports Section
            'Reports' => [
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Spending Overview',
                    'route' => 'business.reports.spending',
                    'active' => ['business.reports.spending'],
                ],
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'label' => 'Worker Performance',
                    'route' => 'business.reports.performance',
                    'active' => ['business.reports.performance'],
                ],
                [
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Shift Analytics',
                    'route' => 'business.reports.analytics',
                    'active' => ['business.reports.analytics'],
                ],
                [
                    'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
                    'label' => 'Export Reports',
                    'route' => 'business.reports.export',
                    'active' => ['business.reports.export'],
                ],
            ],

            // Venue Profile Section
            'Venue Profile' => [
                [
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'label' => 'Business Details',
                    'route' => 'business.profile',
                    'active' => ['business.profile', 'business.profile.edit'],
                ],
                [
                    'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                    'label' => 'Locations',
                    'route' => 'business.locations',
                    'active' => ['business.locations'],
                ],
                [
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'label' => 'Team Members',
                    'route' => 'business.team',
                    'active' => ['business.team'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'label' => 'Verification Documents',
                    'route' => 'business.documents',
                    'active' => ['business.documents'],
                ],
            ],

            // Communication (flat items)
            [
                'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                'label' => 'Messages',
                'route' => 'messages.index',
                'active' => ['messages.index', 'messages.show'],
                'badge' => 'unreadMessagesCount',
            ],
            [
                'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                'label' => 'Notifications',
                'route' => 'notifications.index',
                'active' => ['notifications.index'],
                'badge' => 'unreadNotificationsCount',
            ],

            // Settings & Help (flat items)
            [
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'label' => 'Settings',
                'route' => 'settings.index',
                'active' => ['settings.index', 'settings.*'],
            ],
            [
                'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Help & Support',
                'route' => 'help.index',
                'active' => ['help.index', 'help.*'],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | AGENCY (Staffing Agency) Navigation
        |--------------------------------------------------------------------------
        */
        'agency' => [
            // Dashboard (no section header)
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Dashboard',
                'route' => 'agency.dashboard',
                'active' => ['agency.dashboard'],
            ],

            // Worker Management Section
            'Worker Management' => [
                [
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'label' => 'All Workers',
                    'route' => 'agency.workers.index',
                    'active' => ['agency.workers.index', 'agency.workers.show'],
                ],
                [
                    'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                    'label' => 'Add New Worker',
                    'route' => 'agency.workers.create',
                    'active' => ['agency.workers.create'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Approvals',
                    'route' => 'agency.workers.pending',
                    'active' => ['agency.workers.pending'],
                    'badge' => 'pendingWorkersCount',
                ],
                [
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'label' => 'Worker Groups',
                    'route' => 'agency.workers.groups',
                    'active' => ['agency.workers.groups'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'label' => 'Compliance Status',
                    'route' => 'agency.workers.compliance',
                    'active' => ['agency.workers.compliance'],
                ],
                [
                    'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'label' => 'Worker Documents',
                    'route' => 'agency.workers.documents',
                    'active' => ['agency.workers.documents'],
                ],
            ],

            // Shift Operations Section
            'Shift Operations' => [
                [
                    'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
                    'label' => 'Available Opportunities',
                    'route' => 'agency.shifts.browse',
                    'active' => ['agency.shifts.browse'],
                    'badge' => 'Live',
                ],
                [
                    'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                    'label' => 'Assign Workers',
                    'route' => 'agency.shifts.assign',
                    'active' => ['agency.shifts.assign'],
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                    'label' => 'Active Placements',
                    'route' => 'agency.placements.active',
                    'active' => ['agency.placements.active'],
                ],
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'label' => 'Shift Calendar',
                    'route' => 'agency.shifts.calendar',
                    'active' => ['agency.shifts.calendar'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Placement History',
                    'route' => 'agency.placements.history',
                    'active' => ['agency.placements.history'],
                ],
            ],

            // Venue Relations Section
            'Venue Relations' => [
                [
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'label' => 'Partner Venues',
                    'route' => 'agency.venues.index',
                    'active' => ['agency.venues.index', 'agency.venues.show'],
                ],
                [
                    'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                    'label' => 'Venue Requests',
                    'route' => 'agency.venues.requests',
                    'active' => ['agency.venues.requests'],
                    'badge' => 'venueRequestsCount',
                ],
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Contract Management',
                    'route' => 'agency.venues.contracts',
                    'active' => ['agency.venues.contracts'],
                ],
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'label' => 'Venue Performance',
                    'route' => 'agency.venues.performance',
                    'active' => ['agency.venues.performance'],
                ],
            ],

            // Financials Section
            'Financials' => [
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Revenue Overview',
                    'route' => 'agency.finance.overview',
                    'active' => ['agency.finance.overview'],
                ],
                [
                    'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                    'label' => 'Commission Tracking',
                    'route' => 'agency.finance.commissions',
                    'active' => ['agency.finance.commissions'],
                ],
                [
                    'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                    'label' => 'Worker Payroll',
                    'route' => 'agency.finance.payroll',
                    'active' => ['agency.finance.payroll'],
                ],
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Venue Invoices',
                    'route' => 'agency.finance.invoices',
                    'active' => ['agency.finance.invoices'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Settlements',
                    'route' => 'agency.finance.settlements',
                    'active' => ['agency.finance.settlements'],
                    'badge' => 'pendingSettlementsCount',
                ],
                [
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Financial Reports',
                    'route' => 'agency.finance.reports',
                    'active' => ['agency.finance.reports'],
                ],
            ],

            // Analytics Section
            'Analytics' => [
                [
                    'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                    'label' => 'Performance Dashboard',
                    'route' => 'agency.analytics.dashboard',
                    'active' => ['agency.analytics.dashboard'],
                ],
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'label' => 'Worker Utilization',
                    'route' => 'agency.analytics.utilization',
                    'active' => ['agency.analytics.utilization'],
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Revenue Analytics',
                    'route' => 'agency.analytics.revenue',
                    'active' => ['agency.analytics.revenue'],
                ],
                [
                    'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
                    'label' => 'Custom Reports',
                    'route' => 'agency.analytics.reports',
                    'active' => ['agency.analytics.reports'],
                ],
            ],

            // Agency Profile Section
            'Agency Profile' => [
                [
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'label' => 'Company Details',
                    'route' => 'agency.profile',
                    'active' => ['agency.profile', 'agency.profile.edit'],
                ],
                [
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'label' => 'Team / Staff',
                    'route' => 'agency.team',
                    'active' => ['agency.team'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'label' => 'Licenses & Compliance',
                    'route' => 'agency.compliance',
                    'active' => ['agency.compliance'],
                ],
                [
                    'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
                    'label' => 'Branding Settings',
                    'route' => 'agency.branding',
                    'active' => ['agency.branding'],
                ],
            ],

            // Communication (flat items)
            [
                'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                'label' => 'Messages',
                'route' => 'messages.index',
                'active' => ['messages.index', 'messages.show'],
                'badge' => 'unreadMessagesCount',
            ],
            [
                'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                'label' => 'Notifications',
                'route' => 'notifications.index',
                'active' => ['notifications.index'],
                'badge' => 'unreadNotificationsCount',
            ],

            // Settings & Help (flat items)
            [
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                'label' => 'Settings',
                'route' => 'settings.index',
                'active' => ['settings.index', 'settings.*'],
            ],
            [
                'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                'label' => 'Help & Support',
                'route' => 'help.index',
                'active' => ['help.index', 'help.*'],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | ADMIN (Platform Super Admin) Navigation
        |--------------------------------------------------------------------------
        */
        'admin' => [
            // Dashboard (no section header)
            [
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'label' => 'Admin Dashboard',
                'route' => 'admin.dashboard',
                'active' => ['admin.dashboard'],
            ],

            // Overview Section
            'Overview' => [
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'label' => 'Platform Statistics',
                    'route' => 'admin.statistics',
                    'active' => ['admin.statistics'],
                ],
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'label' => 'Real-time Activity',
                    'route' => 'admin.activity',
                    'active' => ['admin.activity'],
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Revenue Metrics',
                    'route' => 'admin.revenue',
                    'active' => ['admin.revenue'],
                ],
                [
                    'icon' => 'M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01',
                    'label' => 'System Health',
                    'route' => 'admin.system-health',
                    'active' => ['admin.system-health'],
                ],
            ],

            // User Management Section
            'User Management' => [
                [
                    'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'label' => 'All Users',
                    'route' => 'admin.users',
                    'active' => ['admin.users', 'admin.users.show', 'admin.users.edit'],
                ],
                [
                    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                    'label' => 'Workers',
                    'route' => 'admin.users.workers',
                    'active' => ['admin.users.workers'],
                ],
                [
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'label' => 'Venues',
                    'route' => 'admin.users.venues',
                    'active' => ['admin.users.venues'],
                ],
                [
                    'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'label' => 'Agencies',
                    'route' => 'admin.users.agencies',
                    'active' => ['admin.users.agencies'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Verifications',
                    'route' => 'admin.verifications.pending',
                    'active' => ['admin.verifications.pending'],
                    'badge' => 'pendingVerificationsCount',
                ],
                [
                    'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
                    'label' => 'Suspended Accounts',
                    'route' => 'admin.users.suspended',
                    'active' => ['admin.users.suspended'],
                ],
                [
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'User Reports',
                    'route' => 'admin.users.reports',
                    'active' => ['admin.users.reports'],
                ],
            ],

            // Shift Oversight Section
            'Shift Oversight' => [
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'label' => 'All Shifts',
                    'route' => 'admin.shifts.index',
                    'active' => ['admin.shifts.index', 'admin.shifts.show'],
                ],
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'label' => 'Active Shifts',
                    'route' => 'admin.shifts.active',
                    'active' => ['admin.shifts.active'],
                ],
                [
                    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    'label' => 'Disputed Shifts',
                    'route' => 'admin.shifts.disputed',
                    'active' => ['admin.shifts.disputed'],
                    'badge' => 'disputedShiftsCount',
                ],
                [
                    'icon' => 'M6 18L18 6M6 6l12 12',
                    'label' => 'Cancelled Shifts',
                    'route' => 'admin.shifts.cancelled',
                    'active' => ['admin.shifts.cancelled'],
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                    'label' => 'Shift Auditing',
                    'route' => 'admin.shifts.audit',
                    'active' => ['admin.shifts.audit'],
                ],
            ],

            // Financial Admin Section
            'Financial Admin' => [
                [
                    'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                    'label' => 'Escrow Overview',
                    'route' => 'admin.finance.escrow',
                    'active' => ['admin.finance.escrow'],
                ],
                [
                    'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                    'label' => 'All Transactions',
                    'route' => 'admin.finance.transactions',
                    'active' => ['admin.finance.transactions'],
                ],
                [
                    'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Pending Payouts',
                    'route' => 'admin.finance.payouts',
                    'active' => ['admin.finance.payouts'],
                    'badge' => 'pendingPayoutsCount',
                ],
                [
                    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    'label' => 'Disputed Payments',
                    'route' => 'admin.finance.disputed',
                    'active' => ['admin.finance.disputed'],
                    'badge' => 'disputedPaymentsCount',
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Commission Settings',
                    'route' => 'admin.finance.commissions',
                    'active' => ['admin.finance.commissions'],
                ],
                [
                    'icon' => 'M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z',
                    'label' => 'Refund Management',
                    'route' => 'admin.finance.refunds',
                    'active' => ['admin.finance.refunds'],
                ],
                [
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'Financial Reports',
                    'route' => 'admin.finance.reports',
                    'active' => ['admin.finance.reports'],
                ],
            ],

            // Verification Center Section
            'Verification Center' => [
                [
                    'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2',
                    'label' => 'ID Verification',
                    'route' => 'admin.verification.id',
                    'active' => ['admin.verification.id'],
                    'badge' => 'pendingIdVerifications',
                ],
                [
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                    'label' => 'Business Verification',
                    'route' => 'admin.verification.business',
                    'active' => ['admin.verification.business'],
                    'badge' => 'pendingBusinessVerifications',
                ],
                [
                    'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'label' => 'Document Review',
                    'route' => 'admin.verification.documents',
                    'active' => ['admin.verification.documents'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'label' => 'Compliance Checks',
                    'route' => 'admin.verification.compliance',
                    'active' => ['admin.verification.compliance'],
                ],
            ],

            // Moderation Section
            'Moderation' => [
                [
                    'icon' => 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9',
                    'label' => 'Reported Content',
                    'route' => 'admin.moderation.reports',
                    'active' => ['admin.moderation.reports'],
                    'badge' => 'reportedContentCount',
                ],
                [
                    'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                    'label' => 'Review Moderation',
                    'route' => 'admin.moderation.reviews',
                    'active' => ['admin.moderation.reviews'],
                ],
                [
                    'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                    'label' => 'Dispute Resolution',
                    'route' => 'admin.moderation.disputes',
                    'active' => ['admin.moderation.disputes'],
                    'badge' => 'openDisputesCount',
                ],
                [
                    'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
                    'label' => 'Ban Management',
                    'route' => 'admin.moderation.bans',
                    'active' => ['admin.moderation.bans'],
                ],
            ],

            // Analytics & Reports Section
            'Analytics & Reports' => [
                [
                    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    'label' => 'Platform Analytics',
                    'route' => 'admin.analytics.platform',
                    'active' => ['admin.analytics.platform'],
                ],
                [
                    'icon' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                    'label' => 'User Growth',
                    'route' => 'admin.analytics.growth',
                    'active' => ['admin.analytics.growth'],
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Revenue Reports',
                    'route' => 'admin.analytics.revenue',
                    'active' => ['admin.analytics.revenue'],
                ],
                [
                    'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Geographic Insights',
                    'route' => 'admin.analytics.geographic',
                    'active' => ['admin.analytics.geographic'],
                ],
                [
                    'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
                    'label' => 'Export Center',
                    'route' => 'admin.analytics.export',
                    'active' => ['admin.analytics.export'],
                ],
            ],

            // Platform Settings Section
            'Platform Settings' => [
                [
                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                    'label' => 'General Settings',
                    'route' => 'admin.settings.general',
                    'active' => ['admin.settings.general'],
                ],
                [
                    'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Commission Rates',
                    'route' => 'admin.settings.commissions',
                    'active' => ['admin.settings.commissions'],
                ],
                [
                    'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                    'label' => 'Service Areas',
                    'route' => 'admin.settings.areas',
                    'active' => ['admin.settings.areas'],
                ],
                [
                    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                    'label' => 'Job Categories',
                    'route' => 'admin.settings.categories',
                    'active' => ['admin.settings.categories'],
                ],
                [
                    'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                    'label' => 'Skills & Certifications',
                    'route' => 'admin.settings.skills',
                    'active' => ['admin.settings.skills'],
                ],
                [
                    'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'label' => 'Email Templates',
                    'route' => 'admin.settings.emails',
                    'active' => ['admin.settings.emails'],
                ],
                [
                    'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                    'label' => 'Push Notifications',
                    'route' => 'admin.settings.notifications',
                    'active' => ['admin.settings.notifications'],
                ],
                [
                    'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
                    'label' => 'Feature Flags',
                    'route' => 'admin.settings.features',
                    'active' => ['admin.settings.features'],
                ],
            ],

            // Access Control Section
            'Access Control' => [
                [
                    'icon' => 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'label' => 'Admin Users',
                    'route' => 'admin.access.admins',
                    'active' => ['admin.access.admins'],
                ],
                [
                    'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                    'label' => 'Roles & Permissions',
                    'route' => 'admin.access.roles',
                    'active' => ['admin.access.roles'],
                ],
                [
                    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    'label' => 'Audit Logs',
                    'route' => 'admin.access.audit',
                    'active' => ['admin.access.audit'],
                ],
            ],

            // System Section
            'System' => [
                [
                    'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
                    'label' => 'API Keys',
                    'route' => 'admin.system.api-keys',
                    'active' => ['admin.system.api-keys'],
                ],
                [
                    'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                    'label' => 'Webhooks',
                    'route' => 'admin.system.webhooks',
                    'active' => ['admin.system.webhooks'],
                ],
                [
                    'icon' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
                    'label' => 'Integrations',
                    'route' => 'admin.system.integrations',
                    'active' => ['admin.system.integrations'],
                ],
                [
                    'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                    'label' => 'Background Jobs',
                    'route' => 'admin.system.jobs',
                    'active' => ['admin.system.jobs'],
                ],
                [
                    'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'label' => 'System Logs',
                    'route' => 'admin.system.logs',
                    'active' => ['admin.system.logs'],
                ],
            ],

            // Support (flat items)
            [
                'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
                'label' => 'Support Tickets',
                'route' => 'admin.support.tickets',
                'active' => ['admin.support.tickets'],
                'badge' => 'openTicketsCount',
            ],
            [
                'icon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                'label' => 'System Alerts',
                'route' => 'admin.alerts',
                'active' => ['admin.alerts'],
                'badge' => 'systemAlertsCount',
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
            'color' => 'emerald',
        ],
        'business' => [
            'name' => 'Business',
            'badge' => 'Venue Account',
            'color' => 'blue',
        ],
        'agency' => [
            'name' => 'Agency',
            'badge' => 'Staffing Agency',
            'color' => 'purple',
        ],
        'admin' => [
            'name' => 'Admin',
            'badge' => 'Platform Admin',
            'color' => 'red',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Quick Stats Configuration
    |--------------------------------------------------------------------------
    */

    'quick_stats' => [
        'worker' => [
            'shifts_completed',
            'total_earnings',
            'average_rating',
            'profile_completeness',
        ],
        'business' => [
            'active_shifts',
            'pending_applications',
            'total_spent',
            'average_fill_rate',
        ],
        'agency' => [
            'active_workers',
            'active_placements',
            'monthly_revenue',
            'worker_utilization',
        ],
        'admin' => [
            'total_users',
            'active_shifts',
            'pending_verifications',
            'monthly_revenue',
        ],
    ],
];
