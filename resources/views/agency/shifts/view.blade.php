@extends('layouts.authenticated')

@section('title', 'Shift Details')
@section('page-title', 'Shift Details')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('agency.shifts.browse') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <span>Browse Shifts</span>
</a>
<a href="{{ route('agency.workers.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>My Workers</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('agency.shifts.browse') }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium mb-2 inline-block">
                &larr; Back to Marketplace
            </a>
            <h2 class="text-2xl font-bold text-gray-900">{{ $shift->title ?? 'Shift Details' }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $shift->business->name ?? 'Business Name' }}</p>
        </div>
        <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
            {{ $shift->workers_needed ?? 1 }} workers needed
        </span>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Shift Information -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Shift Information</h3>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Date</p>
                        <p class="font-medium text-gray-900">{{ $shift->shift_date ?? 'Date TBD' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Time</p>
                        <p class="font-medium text-gray-900">{{ $shift->start_time ?? '00:00' }} - {{ $shift->end_time ?? '00:00' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Location</p>
                        <p class="font-medium text-gray-900">{{ $shift->location ?? 'Location TBD' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Industry</p>
                        <p class="font-medium text-gray-900">{{ ucfirst($shift->industry ?? 'General') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Hourly Rate</p>
                        <p class="font-medium text-green-600 text-xl">${{ number_format($shift->final_rate ?? 0, 2) }}/hr</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Your Commission</p>
                        <p class="font-medium text-brand-600 text-xl">{{ $commissionRate ?? 10 }}%</p>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Description</h3>
                <p class="text-gray-700">{{ $shift->description ?? 'No description provided.' }}</p>
            </div>

            <!-- Requirements -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Requirements</h3>
                <div class="space-y-2">
                    @forelse($shift->requirements ?? [] as $requirement)
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-gray-700">{{ $requirement }}</span>
                    </div>
                    @empty
                    <p class="text-gray-500">No specific requirements listed</p>
                    @endforelse
                </div>
            </div>

            <!-- Assign Worker Form -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Worker to This Shift</h3>
                <form action="{{ route('agency.shifts.assign') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="shift_id" value="{{ $shift->id ?? '' }}">

                    <div>
                        <label for="worker_id" class="block text-sm font-medium text-gray-700 mb-2">Select Worker</label>
                        <select id="worker_id" name="worker_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">Choose a worker...</option>
                            @forelse($availableWorkers ?? [] as $worker)
                            <option value="{{ $worker->id }}">
                                {{ $worker->name }} - {{ $worker->rating ?? '4.5' }} stars
                                @if($worker->skills ?? false)
                                ({{ implode(', ', array_slice(explode(',', $worker->skills), 0, 2)) }})
                                @endif
                            </option>
                            @empty
                            <option value="" disabled>No available workers</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes for Business (Optional)</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                  placeholder="Any additional information about the worker..."></textarea>
                    </div>

                    <button type="submit" class="w-full px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        Submit Worker for This Shift
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Business Info -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Business Information</h3>
                <div class="flex items-center space-x-4 mb-4">
                    <img src="{{ $shift->business->logo ?? 'https://ui-avatars.com/api/?name=B' }}"
                         alt="Business" class="w-16 h-16 rounded-lg">
                    <div>
                        <p class="font-medium text-gray-900">{{ $shift->business->name ?? 'Business Name' }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $shift->business->rating ?? '4.5' }} rating | {{ $shift->business->shifts_count ?? 0 }} shifts posted
                        </p>
                    </div>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        {{ $shift->business->city ?? 'City' }}, {{ $shift->business->state ?? 'State' }}
                    </div>
                    <div class="flex items-center text-gray-600">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                        </svg>
                        {{ ucfirst($shift->business->industry ?? 'Various') }} Industry
                    </div>
                </div>
            </div>

            <!-- Earnings Breakdown -->
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Earnings Breakdown</h3>
                @php
                    $hourlyRate = $shift->final_rate ?? 15;
                    $hours = 8; // Estimate
                    $workerEarnings = $hourlyRate * $hours;
                    $commissionRate = $commissionRate ?? 10;
                    $yourCommission = $workerEarnings * ($commissionRate / 100);
                @endphp
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Worker Earnings (est.)</span>
                        <span class="font-medium">${{ number_format($workerEarnings, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Your Commission ({{ $commissionRate }}%)</span>
                        <span class="font-medium text-brand-600">${{ number_format($yourCommission, 2) }}</span>
                    </div>
                    <hr class="border-gray-200">
                    <div class="flex justify-between">
                        <span class="text-gray-900 font-medium">Total Value</span>
                        <span class="font-bold text-gray-900">${{ number_format($workerEarnings + $yourCommission, 2) }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">*Based on estimated {{ $hours }} hour shift</p>
            </div>

            <!-- Already Assigned -->
            @if(isset($assignedWorkers) && count($assignedWorkers) > 0)
            <div class="bg-white border border-gray-200 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Workers on This Shift</h3>
                <div class="space-y-3">
                    @foreach($assignedWorkers as $worker)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <img src="{{ $worker->avatar ?? 'https://ui-avatars.com/api/?name=W' }}"
                                 alt="Worker" class="w-8 h-8 rounded-full mr-2">
                            <span class="text-gray-900">{{ $worker->name }}</span>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Assigned</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
