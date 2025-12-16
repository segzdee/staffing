@extends('layouts.app')

@section('title', 'Onboarding Progress - OvertimeStaff')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Progress Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $user->first_name ?? $user->name }}!</h1>
                <p class="text-gray-600 mt-1">Complete your profile to start finding shifts.</p>
            </div>
            <div class="mt-4 md:mt-0">
                @if($progress['summary']['all_required_complete'])
                    <a href="{{ route('worker.activation.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Activate Account
                    </a>
                @endif
            </div>
        </div>

        <!-- Overall Progress Bar -->
        <div class="mt-6">
            <div class="flex justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Overall Progress</span>
                <span class="text-sm font-medium text-gray-700">{{ $progress['summary']['overall_percentage'] }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-500" style="width: {{ $progress['summary']['overall_percentage'] }}%"></div>
            </div>
            <div class="flex justify-between mt-2 text-xs text-gray-500">
                <span>Required: {{ $progress['summary']['required']['completed'] }}/{{ $progress['summary']['required']['total'] }}</span>
                <span>Recommended: {{ $progress['summary']['recommended']['completed'] }}/{{ $progress['summary']['recommended']['total'] }}</span>
            </div>
        </div>
    </div>

    <!-- Next Step Card -->
    @if($nextStep)
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 mb-6 text-white">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold">Next Step: {{ $nextStep['step']->name }}</h3>
                <p class="text-blue-100 mt-1">{{ $nextStep['step']->description ?? 'Complete this step to continue.' }}</p>
                <div class="mt-4">
                    @if($nextStep['route_url'])
                    <a href="{{ $nextStep['route_url'] }}" class="inline-flex items-center px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50">
                        Continue
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Required Steps -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                Required Steps
            </h2>

            <div class="space-y-4">
                @foreach($progress['steps'] as $step)
                    @if($step['is_required'])
                    <div class="flex items-start p-3 rounded-lg {{ $step['status'] === 'completed' ? 'bg-green-50' : 'bg-gray-50' }}">
                        <!-- Status Icon -->
                        <div class="flex-shrink-0">
                            @if($step['status'] === 'completed')
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            @elseif($step['status'] === 'in_progress')
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            @else
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                <span class="text-gray-600 text-sm font-medium">{{ $step['order'] }}</span>
                            </div>
                            @endif
                        </div>

                        <!-- Step Content -->
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-900">{{ $step['name'] }}</h3>
                            @if($step['description'])
                            <p class="text-xs text-gray-500 mt-1">{{ $step['description'] }}</p>
                            @endif
                            @if($step['status'] === 'completed' && $step['completed_at'])
                            <p class="text-xs text-green-600 mt-1">Completed {{ \Carbon\Carbon::parse($step['completed_at'])->diffForHumans() }}</p>
                            @endif
                        </div>

                        <!-- Action -->
                        @if($step['status'] !== 'completed' && $step['route_url'])
                        <a href="{{ $step['route_url'] }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Start
                        </a>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- Recommended Steps -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                Recommended Steps
                <span class="ml-auto text-xs text-gray-500 font-normal">Optional but helpful</span>
            </h2>

            <div class="space-y-4">
                @foreach($progress['steps'] as $step)
                    @if(!$step['is_required'])
                    <div class="flex items-start p-3 rounded-lg {{ $step['status'] === 'completed' ? 'bg-green-50' : 'bg-gray-50' }}">
                        <!-- Status Icon -->
                        <div class="flex-shrink-0">
                            @if($step['status'] === 'completed')
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            @elseif($step['status'] === 'skipped')
                            <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            @else
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            @endif
                        </div>

                        <!-- Step Content -->
                        <div class="ml-4 flex-1">
                            <h3 class="text-sm font-medium text-gray-900">{{ $step['name'] }}</h3>
                            @if($step['has_target'] && $step['target'])
                            <div class="mt-2">
                                <div class="flex items-center text-xs text-gray-500">
                                    <span>Progress: {{ $step['progress_percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="bg-yellow-500 h-1.5 rounded-full" style="width: {{ $step['progress_percentage'] }}%"></div>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        @if($step['status'] !== 'completed' && $step['status'] !== 'skipped')
                        <div class="flex items-center space-x-2">
                            @if($step['route_url'])
                            <a href="{{ $step['route_url'] }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Do
                            </a>
                            @endif
                            @if($step['can_skip'])
                            <button onclick="skipStep('{{ $step['step_id'] }}')" class="text-gray-400 hover:text-gray-600 text-sm">
                                Skip
                            </button>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="bg-blue-50 rounded-lg p-6 mt-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Tips for Success</h3>
        <ul class="space-y-2 text-blue-800">
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Workers with complete profiles get 3x more shift invitations
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Adding more skills helps match you with relevant opportunities
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Set your availability to receive instant shift notifications
            </li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
    async function skipStep(stepId) {
        if (!confirm('Are you sure you want to skip this step? You can complete it later.')) {
            return;
        }

        try {
            const response = await fetch('{{ route("api.worker.onboarding.skip-step") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ step_id: stepId }),
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                alert(data.error || 'Failed to skip step');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    }
</script>
@endpush
@endsection
