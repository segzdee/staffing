@extends('layouts.authenticated')

@section('title', 'Shift Templates')
@section('page-title', 'Shift Templates')

@section('sidebar-nav')
<a href="{{ route('business.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('business.templates.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
    </svg>
    <span>Templates</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Shift Templates</h2>
            <p class="text-sm text-gray-500 mt-1">Create and manage reusable shift templates for faster scheduling</p>
        </div>
        <a href="{{ route('shifts.create') }}?template=new" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
            Create New Template
        </a>
    </div>

    <!-- Active Templates -->
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Active Templates</h3>
        </div>
        <div class="p-6">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($activeTemplates ?? [] as $template)
                <div class="border border-gray-200 rounded-lg p-4 hover:border-brand-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $template->name ?? 'Template Name' }}</h4>
                            <p class="text-sm text-gray-500 mt-1">{{ $template->description ?? 'No description' }}</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                            Active
                        </span>
                    </div>

                    <div class="mt-4 space-y-2 text-sm text-gray-600">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ $template->start_time ?? '09:00' }} - {{ $template->end_time ?? '17:00' }}
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            ${{ number_format($template->hourly_rate ?? 0, 2) }}/hr
                        </div>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $template->workers_needed ?? 1 }} workers needed
                        </div>
                    </div>

                    <div class="mt-4 flex space-x-2">
                        <form action="{{ route('business.templates.createShifts', $template->id ?? 0) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" class="w-full px-3 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 text-sm font-medium">
                                Create Shifts
                            </button>
                        </form>
                        <a href="{{ route('shifts.create') }}?template={{ $template->id ?? 0 }}" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                            Edit
                        </a>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                                <form action="{{ route('business.templates.duplicate', $template->id ?? 0) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Duplicate
                                    </button>
                                </form>
                                <form action="{{ route('business.templates.deactivate', $template->id ?? 0) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-yellow-600 hover:bg-gray-100">
                                        Deactivate
                                    </button>
                                </form>
                                <form action="{{ route('business.templates.delete', $template->id ?? 0) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this template?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No active templates</h3>
                    <p class="mt-2 text-sm text-gray-500">Create your first shift template to speed up scheduling.</p>
                    <a href="{{ route('shifts.create') }}?template=new" class="mt-4 inline-block px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                        Create Template
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Inactive Templates -->
    @if(isset($inactiveTemplates) && $inactiveTemplates->count() > 0)
    <div class="bg-white border border-gray-200 rounded-xl">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Inactive Templates</h3>
        </div>
        <div class="p-6">
            <div class="space-y-3">
                @foreach($inactiveTemplates as $template)
                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                    <div>
                        <h4 class="font-medium text-gray-900">{{ $template->name ?? 'Template' }}</h4>
                        <p class="text-sm text-gray-500">{{ $template->start_time ?? '00:00' }} - {{ $template->end_time ?? '00:00' }}</p>
                    </div>
                    <div class="flex space-x-2">
                        <form action="{{ route('business.templates.activate', $template->id ?? 0) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1 text-green-600 hover:bg-green-50 rounded text-sm">
                                Activate
                            </button>
                        </form>
                        <form action="{{ route('business.templates.delete', $template->id ?? 0) }}" method="POST" onsubmit="return confirm('Delete this template?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 text-red-600 hover:bg-red-50 rounded text-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
