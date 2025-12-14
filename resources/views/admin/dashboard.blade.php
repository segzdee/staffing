@extends('layouts.authenticated')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('sidebar-nav')
<a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('admin.users') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>
    <span>Users</span>
</a>
<a href="{{ route('admin.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    <span>Shifts</span>
</a>
<a href="{{ route('admin.disputes') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <span>Disputes</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
        <h2 class="text-2xl font-bold mb-2">Admin Dashboard</h2>
        <p class="text-purple-100">Platform overview and management tools.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Total Users</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $total_users ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Active Shifts</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $shifts_open ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Pending Verifications</h3>
            <p class="text-3xl font-bold text-gray-900">{{ $pending_verifications ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Platform Revenue</h3>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($total_platform_revenue ?? 0, 0) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
        <p class="text-sm text-gray-500">No recent activity to display.</p>
    </div>
</div>
@endsection
