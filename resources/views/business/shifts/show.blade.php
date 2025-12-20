@extends('layouts.authenticated')

@section('title', $shift->title . ' - Shift Details')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    {{-- Breadcrumb --}}
    <nav class="mb-4 text-sm" aria-label="breadcrumb">
        <ol class="flex flex-wrap items-center gap-2 text-gray-500">
            <li><a href="{{ route('dashboard.index') }}" class="hover:text-gray-900">Dashboard</a></li>
            <li class="before:content-['/'] before:mr-2""><a href="{{ route('business.shifts.index') }}" class="hover:text-gray-900">My Shifts</a></li>
            <li class="before:content-['/'] before:mr-2 text-gray-900 font-medium truncate max-w-[150px] sm:max-w-none" aria-current="page">{{ $shift->title }}</li>
        </ol>
    </nav>

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-6">
        <div class="min-w-0">
            <h4 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 break-words">{{ $shift->title }}</h4>
            @php
                $statusColors = [
                    'open' => 'bg-green-100 text-green-800',
                    'completed' => 'bg-gray-100 text-gray-800',
                    'assigned' => 'bg-blue-100 text-blue-800',
                    'cancelled' => 'bg-red-100 text-red-800',
                ];
                $statusColor = $statusColors[$shift->status] ?? 'bg-yellow-100 text-yellow-800';
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                {{ ucfirst($shift->status) }}
            </span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('business.shifts.applications', $shift->id) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors min-h-[44px]">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Applications
                @if($shift->applications->where('status', 'pending')->count() > 0)
                    <span class="ml-2 px-2 py-0.5 bg-white text-gray-900 text-xs rounded-full">{{ $shift->applications->where('status', 'pending')->count() }}</span>
                @endif
            </a>
            <a href="{{ route('business.shifts.edit', $shift->id) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors min-h-[44px]">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start justify-between" role="alert">
            <p class="text-green-800">{{ session('success') }}</p>
            <button type="button" onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800 min-h-[40px] min-w-[40px] flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Shift Details Card --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 bg-gray-50">
                    <h6 class="text-sm sm:text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Shift Details
                    </h6>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                        {{-- Date & Time --}}
                        <div>
                            <h6 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Date & Time
                            </h6>
                            <p class="text-gray-900 mb-1">{{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}</p>
                            <p class="text-gray-700">{{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</p>
                        </div>
                        {{-- Staffing --}}
                        <div>
                            <h6 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Staffing
                            </h6>
                            @php
                                $percentage = $shift->required_workers > 0 ? ($shift->filled_workers / $shift->required_workers) * 100 : 0;
                            @endphp
                            <div class="h-5 bg-gray-200 rounded-full overflow-hidden mb-2">
                                <div class="h-full {{ $percentage >= 100 ? 'bg-green-500' : 'bg-blue-500' }} flex items-center justify-center text-xs text-white font-medium"
                                     style="width: {{ max($percentage, 20) }}%">
                                    {{ $shift->filled_workers }}/{{ $shift->required_workers }}
                                </div>
                            </div>
                            <p class="text-sm text-gray-500">{{ $shift->required_workers - $shift->filled_workers }} positions remaining</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                        {{-- Pay Rate --}}
                        <div>
                            <h6 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Pay Rate
                            </h6>
                            <p class="text-xl sm:text-2xl font-bold text-green-600">${{ number_format($shift->hourly_rate, 2) }}/hr</p>
                        </div>
                        {{-- Location --}}
                        <div>
                            <h6 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Location
                            </h6>
                            @if($shift->venue)
                                <p class="text-gray-900 font-medium mb-1">
                                    {{ $shift->venue->name }}
                                    @if($shift->venue->type)
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ ucfirst(str_replace('_', ' ', $shift->venue->type)) }}</span>
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $shift->venue->address }}
                                    @if($shift->venue->address_line_2)
                                        <br>{{ $shift->venue->address_line_2 }}
                                    @endif
                                    <br>{{ $shift->venue->city }}, {{ $shift->venue->state }} {{ $shift->venue->postal_code }}
                                </p>
                            @else
                                <p class="text-gray-900">{{ $shift->location_name ?? 'N/A' }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $shift->location_address ?? '' }}
                                    @if($shift->location_city)
                                        <br>{{ $shift->location_city }}, {{ $shift->location_state }} {{ $shift->location_zip ?? '' }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($shift->description)
                        <div class="mb-4">
                            <h6 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                                </svg>
                                Description
                            </h6>
                            <p class="text-gray-700">{{ $shift->description }}</p>
                        </div>
                    @endif

                    @if($shift->requirements)
                        <div>
                            <h6 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Requirements
                            </h6>
                            <p class="text-gray-700">{{ $shift->requirements }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assigned Workers --}}
            @if($shift->assignments->count() > 0)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 bg-gray-50">
                        <h6 class="text-sm sm:text-base font-semibold text-gray-900 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Assigned Workers
                        </h6>
                    </div>

                    {{-- Mobile View (Cards) --}}
                    <div class="block sm:hidden divide-y divide-gray-200">
                        @foreach($shift->assignments as $assignment)
                            <div class="p-4">
                                <div class="flex items-start gap-3 mb-3">
                                    <img src="{{ $assignment->worker->avatar ?? asset('images/default-avatar.png') }}"
                                         alt="{{ $assignment->worker->name }}"
                                         class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ $assignment->worker->name }}</p>
                                        @if($assignment->worker->rating_as_worker)
                                            <p class="text-sm text-gray-500 flex items-center">
                                                <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                {{ number_format($assignment->worker->rating_as_worker, 1) }}
                                            </p>
                                        @endif
                                    </div>
                                    @php
                                        $assignmentStatusColors = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'checked_in' => 'bg-yellow-100 text-yellow-800',
                                            'assigned' => 'bg-blue-100 text-blue-800',
                                        ];
                                        $assignmentStatusColor = $assignmentStatusColors[$assignment->status] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded {{ $assignmentStatusColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500 mb-3">
                                    Assigned: {{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, g:i A') : 'N/A' }}
                                </p>
                                <div class="flex gap-2">
                                    @if($assignment->status === 'assigned')
                                        <form action="{{ route('business.shifts.unassignWorker', $assignment->id) }}" method="POST"
                                              onsubmit="return confirm('Are you sure you want to unassign this worker?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center min-h-[40px] min-w-[40px] p-2 text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('messages.worker', ['worker_id' => $assignment->worker->id, 'shift_id' => $shift->id]) }}"
                                       class="inline-flex items-center justify-center min-h-[40px] min-w-[40px] p-2 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop View (Table) --}}
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Worker</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned At</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($shift->assignments as $assignment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <img src="{{ $assignment->worker->avatar ?? asset('images/default-avatar.png') }}"
                                                     alt="{{ $assignment->worker->name }}"
                                                     class="w-10 h-10 rounded-full object-cover mr-3">
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $assignment->worker->name }}</p>
                                                    @if($assignment->worker->rating_as_worker)
                                                        <p class="text-sm text-gray-500 flex items-center">
                                                            <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                            </svg>
                                                            {{ number_format($assignment->worker->rating_as_worker, 1) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $assignmentStatusColors = [
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'checked_in' => 'bg-yellow-100 text-yellow-800',
                                                    'assigned' => 'bg-blue-100 text-blue-800',
                                                ];
                                                $assignmentStatusColor = $assignmentStatusColors[$assignment->status] ?? 'bg-gray-100 text-gray-800';
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $assignmentStatusColor }}">
                                                {{ ucfirst(str_replace('_', ' ', $assignment->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, g:i A') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <div class="flex justify-end gap-2">
                                                @if($assignment->status === 'assigned')
                                                    <form action="{{ route('business.shifts.unassignWorker', $assignment->id) }}" method="POST"
                                                          onsubmit="return confirm('Are you sure you want to unassign this worker?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center justify-center min-h-[40px] min-w-[40px] p-2 text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('messages.worker', ['worker_id' => $assignment->worker->id, 'shift_id' => $shift->id]) }}"
                                                   class="inline-flex items-center justify-center min-h-[40px] min-w-[40px] p-2 text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 bg-gray-50">
                    <h6 class="text-sm sm:text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Quick Actions
                    </h6>
                </div>
                <div class="p-4 sm:p-6 space-y-3">
                    <a href="{{ route('business.shifts.applications', $shift->id) }}"
                       class="flex items-center justify-center w-full px-4 py-3 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors min-h-[44px]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        View Applications
                    </a>
                    <a href="{{ route('business.shifts.edit', $shift->id) }}"
                       class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors min-h-[44px]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Shift
                    </a>
                    <form action="{{ route('business.shifts.duplicate', $shift->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="flex items-center justify-center w-full px-4 py-3 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors min-h-[44px]">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Duplicate Shift
                        </button>
                    </form>
                    @if($shift->status === 'open')
                        <form action="{{ route('business.shifts.cancel', $shift->id) }}" method="POST"
                              onsubmit="return confirm('Are you sure you want to cancel this shift?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="flex items-center justify-center w-full px-4 py-3 border border-red-200 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition-colors min-h-[44px]">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel Shift
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Shift Stats --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200 bg-gray-50">
                    <h6 class="text-sm sm:text-base font-semibold text-gray-900 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                        Shift Stats
                    </h6>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900">{{ $shift->applications->count() }}</p>
                            <p class="text-sm text-gray-500">Applications</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900">{{ $shift->assignments->count() }}</p>
                            <p class="text-sm text-gray-500">Assigned</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-900">{{ $shift->assignments->where('status', 'completed')->count() }}</p>
                            <p class="text-sm text-gray-500">Completed</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            @php
                                $urgencyColors = [
                                    'critical' => 'text-red-600',
                                    'urgent' => 'text-yellow-600',
                                    'normal' => 'text-gray-600',
                                ];
                                $urgencyColor = $urgencyColors[$shift->urgency_level ?? 'normal'] ?? 'text-gray-600';
                            @endphp
                            <p class="text-2xl font-bold {{ $urgencyColor }}">
                                {{ ucfirst($shift->urgency_level ?? 'normal') }}
                            </p>
                            <p class="text-sm text-gray-500">Urgency</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
