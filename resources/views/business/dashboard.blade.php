@extends('layouts.authenticated')

@section('title', 'Business Dashboard')
@section('page-title', 'Business Dashboard')

@section('sidebar-nav')
<a href="{{ route('business.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('shifts.create') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>Post Shift</span>
</a>
<a href="{{ route('business.available-workers') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span>Find Workers</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
        <h2 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}!</h2>
        <p class="text-blue-100">Manage your shifts and find qualified workers.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Active Shifts</h3>
            <p class="text-3xl font-bold text-gray-900">0</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Total Applications</h3>
            <p class="text-3xl font-bold text-gray-900">0</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">Workers Assigned</h3>
            <p class="text-3xl font-bold text-gray-900">0</p>
        </div>
        <div class="bg-white rounded-xl p-6 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-600 mb-2">This Month</h3>
            <p class="text-3xl font-bold text-gray-900">$0.00</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Recent Shifts</h3>
        </div>
        <div class="p-12 text-center">
            <h3 class="mt-4 text-lg font-medium text-gray-900">No shifts posted yet</h3>
            <p class="mt-2 text-sm text-gray-500">Start by posting your first shift.</p>
            <a href="{{ route('shifts.create') }}" class="mt-6 inline-flex items-center px-6 py-3 text-white bg-brand-600 hover:bg-brand-700 rounded-lg">
                Post Your First Shift
            </a>
        </div>
    </div>
</div>
@endsection
