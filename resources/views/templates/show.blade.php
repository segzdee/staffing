@extends('layouts.authenticated')

@section('title', 'Template Details')
@section('page-title', 'Template Details')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.templates.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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
            <a href="{{ route('business.templates.index') }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium mb-2 inline-block">
                &larr; Back to Templates
            </a>
            <h2 class="text-2xl font-bold text-gray-900">{{ $template->name ?? 'Template Details' }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $template->description ?? 'View and manage this shift template' }}</p>
        </div>
        <div class="flex space-x-3">
            <form action="{{ route('business.templates.createShifts', $template->id ?? 0) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                    Create Shifts from Template
                </button>
            </form>
            <a href="{{ route('shifts.create') }}?template={{ $template->id ?? 0 }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-medium">
                Edit Template
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Shift Information -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Shift Information</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Shift Title</p>
                        <p class="font-medium text-gray-900">{{ $template->title ?? 'Shift Title' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Industry</p>
                        <p class="font-medium text-gray-900">{{ ucfirst($template->industry ?? 'General') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Start Time</p>
                        <p class="font-medium text-gray-900">{{ $template->start_time ?? '09:00' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">End Time</p>
                        <p class="font-medium text-gray-900">{{ $template->end_time ?? '17:00' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Break Duration</p>
                        <p class="font-medium text-gray-900">{{ $template->break_duration ?? 30 }} minutes</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Workers Needed</p>
                        <p class="font-medium text-gray-900">{{ $template->workers_needed ?? 1 }}</p>
                    </div>
                </div>
            </div>

            <!-- Compensation -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Compensation</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Hourly Rate</p>
                        <p class="font-medium text-green-600 text-xl">${{ number_format($template->hourly_rate ?? 0, 2) }}/hr</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Overtime Rate</p>
                        <p class="font-medium text-gray-900">${{ number_format($template->overtime_rate ?? 0, 2) }}/hr</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Estimated Shift Value</p>
                        @php
                            $hours = 8; // Default estimate
                            $estimatedValue = ($template->hourly_rate ?? 15) * $hours * ($template->workers_needed ?? 1);
                        @endphp
                        <p class="font-medium text-gray-900">${{ number_format($estimatedValue, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tips Included</p>
                        <p class="font-medium text-gray-900">{{ ($template->tips_included ?? false) ? 'Yes' : 'No' }}</p>
                    </div>
                </div>
            </div>

            <!-- Requirements -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Requirements</h3>
                @if($template->requirements ?? false)
                <div class="space-y-2">
                    @foreach(explode("\n", $template->requirements) as $requirement)
                    @if(trim($requirement))
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">{{ trim($requirement) }}</span>
                    </div>
                    @endif
                    @endforeach
                </div>
                @else
                <p class="text-gray-500">No specific requirements</p>
                @endif

                @if($template->skills ?? false)
                <div class="mt-4">
                    <p class="text-sm text-gray-500 mb-2">Required Skills</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(explode(',', $template->skills) as $skill)
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ trim($skill) }}</span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>
                @php
                    $isActive = $template->is_active ?? true;
                @endphp
                <span class="px-3 py-1 rounded-full text-sm font-medium {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                    {{ $isActive ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <!-- Usage Stats -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Usage Statistics</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500">Shifts Created</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $template->shifts_created ?? 0 }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Last Used</p>
                        <p class="font-medium text-gray-900">{{ $template->last_used_at ?? 'Never' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Created</p>
                        <p class="font-medium text-gray-900">{{ $template->created_at ?? now()->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-3">
                    <form action="{{ route('business.templates.duplicate', $template->id ?? 0) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 text-left text-gray-700 hover:bg-gray-50 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Duplicate Template
                        </button>
                    </form>

                    @if($template->is_active ?? true)
                    <form action="{{ route('business.templates.deactivate', $template->id ?? 0) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 text-left text-yellow-600 hover:bg-yellow-50 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Deactivate
                        </button>
                    </form>
                    @else
                    <form action="{{ route('business.templates.activate', $template->id ?? 0) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 text-left text-green-600 hover:bg-green-50 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Activate
                        </button>
                    </form>
                    @endif

                    <form action="{{ route('business.templates.delete', $template->id ?? 0) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this template?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 text-left text-red-600 hover:bg-red-50 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Template
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
