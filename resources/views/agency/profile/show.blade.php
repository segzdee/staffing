@extends('layouts.authenticated')

@section('title', 'Agency Profile')
@section('page-title', 'Agency Profile')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('agency.profile') }}" class="sidebar-link active flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
</a>
<a href="{{ route('agency.workers.index') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span>Workers</span>
</a>
<a href="{{ route('agency.clients.index') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    <span>Clients</span>
</a>
<a href="{{ route('agency.shifts.browse') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <span>Browse Shifts</span>
</a>
<a href="{{ route('agency.assignments') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
    </svg>
    <span>Assignments</span>
</a>
<a href="{{ route('agency.commissions') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Commissions</span>
</a>
<a href="{{ route('agency.analytics') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    <span>Analytics</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Profile Completeness Card -->
        <div class="bg-gradient-to-r from-primary to-primary/80 rounded-xl p-6 text-primary-foreground">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Profile Completeness</h2>
                <span class="text-3xl font-bold">{{ $profileCompleteness ?? 0 }}%</span>
            </div>
            <div class="w-full bg-primary-foreground/20 rounded-full h-3">
                <div class="bg-primary-foreground h-3 rounded-full transition-all" style="width: {{ $profileCompleteness ?? 0 }}%"></div>
            </div>
            <p class="text-sm opacity-90 mt-3">Complete your agency profile to attract more clients and workers.</p>
        </div>

        <!-- Agency Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-card rounded-xl border border-border p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-info/10 rounded-lg">
                        <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-muted-foreground">Total Workers</p>
                        <p class="text-2xl font-bold text-foreground">{{ $stats['total_workers'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-card rounded-xl border border-border p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-success/10 rounded-lg">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-muted-foreground">Active Workers</p>
                        <p class="text-2xl font-bold text-foreground">{{ $stats['active_workers'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-card rounded-xl border border-border p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-warning/10 rounded-lg">
                        <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-muted-foreground">Total Clients</p>
                        <p class="text-2xl font-bold text-foreground">{{ $stats['total_clients'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Information -->
        <div class="bg-card rounded-xl border border-border overflow-hidden">
            <div class="p-6 border-b border-border flex items-center justify-between">
                <h2 class="text-xl font-bold text-foreground">Agency Information</h2>
                <a href="{{ route('agency.profile.edit') }}" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Profile
                </a>
            </div>
            <div class="p-6 space-y-6">
                <!-- Agency Logo/Avatar -->
                <div class="flex items-center space-x-4">
                    <div class="w-20 h-20 rounded-full bg-muted overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ asset($user->avatar) }}" alt="Agency Logo" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-muted-foreground">
                                {{ strtoupper(substr($profile->agency_name ?? $user->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">{{ $profile->agency_name ?? $user->name }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                    </div>
                </div>

                <!-- Profile Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Contact Name</label>
                        <p class="text-foreground">{{ $profile->contact_name ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Phone</label>
                        <p class="text-foreground">{{ $profile->phone ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Email</label>
                        <p class="text-foreground">{{ $profile->email ?? $user->email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Commission Rate</label>
                        <p class="text-foreground">{{ $profile->commission_rate ?? '0' }}%</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-muted-foreground">Address</label>
                        <p class="text-foreground">
                            @if($profile && ($profile->address || $profile->city))
                                {{ $profile->address }}<br>
                                {{ $profile->city }}{{ $profile->state ? ', ' . $profile->state : '' }} {{ $profile->zip_code }}<br>
                                {{ $profile->country }}
                            @else
                                Not set
                            @endif
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-muted-foreground">Description</label>
                        <p class="text-foreground">{{ $profile->description ?? 'No description provided.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('agency.workers.add') }}" class="bg-card rounded-xl border border-border p-6 hover:border-primary/50 transition-colors">
                <div class="flex items-center">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-foreground">Add Workers</h3>
                        <p class="text-sm text-muted-foreground">Invite workers to join your agency</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('agency.clients.create') }}" class="bg-card rounded-xl border border-border p-6 hover:border-primary/50 transition-colors">
                <div class="flex items-center">
                    <div class="p-3 bg-success/10 rounded-lg">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-foreground">Add Client</h3>
                        <p class="text-sm text-muted-foreground">Register a new business client</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
