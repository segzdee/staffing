@extends('layouts.authenticated')

@section('title', 'Shift Details')
@section('page-title', 'Shift Details')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">
    <!-- Session Messages -->
    @if(session('success'))
    <div class="bg-green-50 text-green-700 p-4 rounded-lg">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 text-red-700 p-4 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    <div class="flex items-center space-x-4">
        <a href="{{ route('shifts.index') }}" class="text-gray-600 hover:text-gray-900">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-900">{{ $shift->title }}</h1>
        @if($shift->status === 'cancelled')
            <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium">Cancelled</span>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Shift Details</h2>
                <div class="space-y-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Role Type</p>
                            <p class="text-gray-600">{{ ucfirst(str_replace('_', ' ', $shift->role_type ?? 'General')) }}</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Date & Time</p>
                            <p class="text-gray-600">{{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}</p>
                            <p class="text-gray-600">
                                {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}
                                ({{ number_format($shift->duration_hours, 1) }} hours)
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-gray-900">Location</p>
                            <p class="text-gray-600">
                                @if($shift->venue)
                                    {{ $shift->venue->name }}<br>
                                    {{ $shift->venue->address }}<br>
                                @else
                                    {{ $shift->location_address }}<br>
                                @endif
                                {{ $shift->location_city }}, {{ $shift->location_state }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Description</h2>
                <div class="text-gray-600 leading-relaxed prose max-w-none">
                    {!! nl2br(e($shift->description)) !!}
                </div>
                
                @if($shift->requirements)
                <div class="mt-4">
                    <h3 class="font-medium text-gray-900 mb-2">Requirements:</h3>
                    <div class="text-gray-600">
                        {!! nl2br(e($shift->requirements)) !!}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-6">
                <div class="text-center mb-6">
                    @php
                        $hourlyRate = $shift->final_rate ? \App\Support\Money::toDecimal($shift->final_rate) : 0;
                        $totalPay = $hourlyRate * $shift->duration_hours;
                    @endphp
                    <p class="text-3xl font-bold text-gray-900">${{ number_format($hourlyRate, 2) }}/hr</p>
                    <p class="text-sm text-gray-500 mt-1">Total: ${{ number_format($totalPay, 2) }}</p>
                </div>
                
                @if(auth()->user() && auth()->user()->isWorker())
                    @if($hasApplied)
                        <button disabled class="w-full px-6 py-3 bg-gray-100 text-gray-500 font-medium rounded-lg cursor-not-allowed">
                            Applied
                        </button>
                    @elseif($shift->status === 'open')
                        <form action="{{ route('market.apply', $shift->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                                Apply for This Shift
                            </button>
                        </form>
                    @else
                        <button disabled class="w-full px-6 py-3 bg-gray-100 text-gray-500 font-medium rounded-lg cursor-not-allowed">
                            {{ ucfirst($shift->status) }}
                        </button>
                    @endif
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Workers Needed</span>
                        <span class="font-medium text-gray-900">{{ $shift->required_workers }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Filled Slots</span>
                        <span class="font-medium text-gray-900">{{ $shift->filled_workers }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Business Info</h3>
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center text-xl font-bold text-gray-500">
                        {{ substr($shift->business->name ?? 'B', 0, 1) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $shift->business->name ?? 'Unknown Business' }}</p>
                        @if($shift->business->businessProfile)
                        <div class="flex items-center text-sm text-gray-500">
                            {{ $shift->business->businessProfile->industry ?? 'Business' }}
                        </div>
                        @endif
                    </div>
                </div>
                @if($shift->business->businessProfile)
                <p class="text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($shift->business->businessProfile->description, 150) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection