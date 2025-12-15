@extends('layouts.authenticated')

@section('title', 'Business Profile')
@section('page-title', 'Business Profile')

@section('sidebar-nav')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.profile') }}" class="sidebar-link active flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('shifts.create') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
    </svg>
    <span>Post Shift</span>
</a>
<a href="{{ route('business.available-workers') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>Find Workers</span>
</a>
<a href="{{ route('business.analytics') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
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
            <p class="text-sm opacity-90 mt-3">Complete your business profile to attract quality workers.</p>
        </div>

        <!-- Business Information -->
        <div class="bg-card rounded-xl border border-border overflow-hidden">
            <div class="p-6 border-b border-border flex items-center justify-between">
                <h2 class="text-xl font-bold text-foreground">Business Information</h2>
                <a href="{{ route('settings.index') }}" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Profile
                </a>
            </div>
            <div class="p-6 space-y-6">
                <!-- Business Logo/Avatar -->
                <div class="flex items-center space-x-4">
                    <div class="w-20 h-20 rounded-full bg-muted overflow-hidden">
                        @if($user->avatar)
                            <img src="{{ asset($user->avatar) }}" alt="Business Logo" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-muted-foreground">
                                {{ strtoupper(substr($profile->company_name ?? $user->name, 0, 2)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">{{ $profile->company_name ?? $user->name }}</h3>
                        <p class="text-sm text-muted-foreground">{{ $user->email }}</p>
                        @if($profile && $profile->business_type)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary mt-1">
                                {{ ucfirst($profile->business_type) }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Profile Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Company Name</label>
                        <p class="text-foreground">{{ $profile->company_name ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Business Type</label>
                        <p class="text-foreground">{{ ucfirst($profile->business_type ?? 'Not set') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Phone</label>
                        <p class="text-foreground">{{ $profile->phone ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-muted-foreground">Website</label>
                        <p class="text-foreground">{{ $profile->website ?? 'Not set' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-muted-foreground">Address</label>
                        <p class="text-foreground">
                            @if($profile && ($profile->address || $profile->city))
                                {{ $profile->address }}<br>
                                {{ $profile->city }}{{ $profile->state ? ', ' . $profile->state : '' }} {{ $profile->zip_code }}
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

        <!-- Verification Status -->
        <div class="bg-card rounded-xl border border-border p-6">
            <h3 class="text-lg font-semibold text-foreground mb-4">Verification Status</h3>
            <div class="flex items-center space-x-4">
                @if($user->is_verified_business)
                    <div class="flex items-center text-success">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Verified Business</span>
                    </div>
                @else
                    <div class="flex items-center text-warning">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium">Pending Verification</span>
                    </div>
                @endif
            </div>
            @if(!$user->is_verified_business)
                <p class="text-sm text-muted-foreground mt-2">
                    Complete your profile and add payment information to get verified. Verified businesses get more worker applications.
                </p>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('shifts.create') }}" class="bg-card rounded-xl border border-border p-6 hover:border-primary/50 transition-colors">
                <div class="flex items-center">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-foreground">Post a Shift</h3>
                        <p class="text-sm text-muted-foreground">Create a new shift listing</p>
                    </div>
                </div>
            </a>
            <a href="{{ route('business.available-workers') }}" class="bg-card rounded-xl border border-border p-6 hover:border-primary/50 transition-colors">
                <div class="flex items-center">
                    <div class="p-3 bg-success/10 rounded-lg">
                        <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-foreground">Find Workers</h3>
                        <p class="text-sm text-muted-foreground">Browse available workers</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
