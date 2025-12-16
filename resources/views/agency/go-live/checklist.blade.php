@extends('layouts.authenticated')

@section('title', 'Go-Live Checklist')
@section('page-title', 'Go-Live Checklist')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('agency.go-live.checklist') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <span>Go-Live Checklist</span>
</a>
<a href="{{ route('agency.stripe.onboarding') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
    </svg>
    <span>Payment Setup</span>
</a>
<a href="{{ route('agency.workers.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span>Workers</span>
</a>
<a href="{{ route('agency.profile') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    <span>Agency Profile</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-5xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Go-Live Checklist</h1>
            <p class="mt-1 text-gray-600">Complete all steps below to activate your agency and start receiving shift assignments.</p>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Pending Review Notice -->
        @if($isPendingReview)
        <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Go-Live Request Under Review</h3>
                    <p class="mt-1 text-sm text-yellow-700">Your go-live request has been submitted. Our team will review and activate your account within 24-48 hours.</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Progress Overview -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            @if($isReady)
                            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            @else
                            <div class="w-16 h-16 rounded-full bg-brand-100 flex items-center justify-center">
                                <span class="text-2xl font-bold text-brand-600">{{ $progress['percentage'] }}%</span>
                            </div>
                            @endif
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">
                                @if($isReady)
                                Ready to Go Live!
                                @else
                                {{ $progress['completed'] }} of {{ $progress['total'] }} Steps Complete
                                @endif
                            </h2>
                            <p class="text-sm text-gray-500">
                                @if($isReady)
                                All requirements met. Submit your go-live request to activate.
                                @else
                                Complete the remaining steps to activate your agency.
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-brand-600 h-2 rounded-full transition-all duration-500" style="width: {{ $progress['percentage'] }}%"></div>
                        </div>
                    </div>
                </div>

                <!-- Compliance Score -->
                <div class="mt-6 md:mt-0 md:ml-8 flex-shrink-0">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-3xl font-bold @if($compliance['score'] >= 75) text-green-600 @elseif($compliance['score'] >= 60) text-yellow-600 @else text-red-600 @endif">
                            {{ $compliance['grade'] }}
                        </div>
                        <div class="text-sm text-gray-500">Compliance Grade</div>
                        <div class="text-xs text-gray-400 mt-1">{{ round($compliance['score']) }}% Score</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Checklist Items -->
        <div class="space-y-4">
            @foreach($checklist as $key => $item)
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden transition-all duration-200 hover:shadow-md">
                <div class="p-6">
                    <div class="flex items-start">
                        <!-- Status Icon -->
                        <div class="flex-shrink-0 mr-4">
                            @if($item['completed'])
                            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            @else
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-lg font-semibold text-gray-400">{{ $item['priority'] }}</span>
                            </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $item['title'] }}
                                </h3>
                                @if($item['completed'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                                @elseif(isset($item['percentage']) && $item['percentage'] > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $item['percentage'] }}% Done
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Pending
                                </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ $item['description'] }}</p>

                            <!-- Details (if available) -->
                            @if(isset($item['details']) && !$item['completed'])
                            <div class="mt-3 text-sm text-gray-600">
                                @if($key === 'workers_onboarded' && isset($item['details']['active_workers']))
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    {{ $item['details']['active_workers'] }} / {{ $item['details']['required_workers'] }} workers
                                </span>
                                @elseif($key === 'profile_complete' && isset($item['details']['missing_fields']))
                                <span class="text-red-600">Missing: {{ implode(', ', array_map(fn($f) => str_replace('_', ' ', $f), $item['details']['missing_fields'])) }}</span>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Action Button -->
                        <div class="flex-shrink-0 ml-4">
                            <a href="{{ $item['action_url'] }}"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-150 @if($item['completed']) border-green-200 text-green-700 hover:bg-green-50 @endif">
                                {{ $item['action_label'] }}
                                <svg class="ml-2 -mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Progress Bar for partial completion -->
                    @if(isset($item['percentage']) && $item['percentage'] > 0 && $item['percentage'] < 100)
                    <div class="mt-4 ml-14">
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-yellow-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $item['percentage'] }}%"></div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        <!-- Go-Live Action -->
        @if($isReady && !$isPendingReview)
        <div class="mt-8 bg-gradient-to-r from-brand-600 to-brand-700 rounded-xl p-6 text-white">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h3 class="text-xl font-bold">You're Ready to Go Live!</h3>
                    <p class="mt-1 text-brand-100">All checklist items have been completed. Submit your request to activate your agency.</p>
                </div>
                <form action="{{ route('agency.go-live.request') }}" method="POST" class="mt-4 md:mt-0">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 border border-white rounded-lg text-sm font-semibold text-brand-700 bg-white hover:bg-brand-50 transition-colors duration-150 shadow-lg">
                        <svg class="mr-2 -ml-1 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Request Go-Live
                    </button>
                </form>
            </div>
        </div>
        @elseif(!$isReady)
        <!-- Next Steps -->
        @if(!empty($nextSteps))
        <div class="mt-8 bg-blue-50 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">Recommended Next Steps</h3>
            <div class="space-y-3">
                @foreach($nextSteps as $step)
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                        <span class="text-xs font-bold text-blue-600">{{ $step['step'] }}</span>
                    </div>
                    <div class="flex-1">
                        <a href="{{ $step['action_url'] }}" class="text-blue-700 hover:text-blue-800 font-medium">
                            {{ $step['title'] }}
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

        <!-- Compliance Details -->
        <div class="mt-8 bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Compliance Status</h3>
                <button onclick="refreshCompliance()"
                        class="text-sm text-brand-600 hover:text-brand-700 font-medium flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($compliance['category_scores'] ?? [] as $category => $data)
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">{{ ucwords(str_replace('_', ' ', $category)) }}</span>
                        <span class="text-sm font-bold @if($data['score'] >= 100) text-green-600 @elseif($data['score'] >= 50) text-yellow-600 @else text-red-600 @endif">
                            {{ $data['score'] }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500 @if($data['score'] >= 100) bg-green-500 @elseif($data['score'] >= 50) bg-yellow-500 @else bg-red-500 @endif"
                             style="width: {{ $data['score'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">{{ $data['message'] ?? '' }}</p>
                </div>
                @endforeach
            </div>

            @if(!empty($compliance['expires_soon']))
            <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                <h4 class="text-sm font-semibold text-yellow-800 mb-2">Documents Expiring Soon</h4>
                <ul class="space-y-1">
                    @foreach($compliance['expires_soon'] as $expiring)
                    <li class="text-sm text-yellow-700">
                        <span class="font-medium">{{ ucwords(str_replace('_', ' ', $expiring['type'])) }}</span>
                        - Expires in {{ $expiring['days_remaining'] }} days
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <!-- Help Section -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Need help? <a href="{{ route('contact') }}" class="text-brand-600 hover:text-brand-700 font-medium">Contact our support team</a></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function refreshCompliance() {
        // Show loading state
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<svg class="animate-spin w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Refreshing...';
        button.disabled = true;

        fetch('{{ route("agency.go-live.refresh-compliance") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated data
                window.location.reload();
            } else {
                alert(data.error || 'Failed to refresh compliance status.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to refresh compliance status.');
        })
        .finally(() => {
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
</script>
@endpush
@endsection
