@extends('layouts.worker')

@section('title', 'Violation History')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('worker.compliance.index') }}" class="text-blue-600 hover:underline text-sm">
            &larr; Back to Compliance Dashboard
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Violation History</h1>

    @if($violations->count() > 0)
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="divide-y divide-gray-200">
            @foreach($violations as $violation)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <!-- Severity Badge -->
                            <span class="px-2 py-1 text-xs rounded-full font-medium {{
                                $violation->severity === 'critical' ? 'bg-red-100 text-red-800' :
                                ($violation->severity === 'violation' ? 'bg-orange-100 text-orange-800' :
                                ($violation->severity === 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'))
                            }}">
                                {{ ucfirst($violation->severity) }}
                            </span>

                            <!-- Status Badge -->
                            <span class="px-2 py-1 text-xs rounded-full {{
                                in_array($violation->status, ['resolved', 'exempted'])
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-gray-100 text-gray-800'
                            }}">
                                {{ ucfirst($violation->status) }}
                            </span>

                            @if($violation->was_blocked)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                Blocked
                            </span>
                            @endif
                        </div>

                        <h3 class="font-medium text-gray-900">{{ $violation->laborLawRule->name }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $violation->description }}</p>

                        @if($violation->violation_data)
                        <div class="mt-2 text-sm text-gray-500">
                            @if(isset($violation->violation_data['actual']) && isset($violation->violation_data['limit']))
                            <span>Actual: {{ $violation->violation_data['actual'] }} | Limit: {{ $violation->violation_data['limit'] }}</span>
                            @endif
                        </div>
                        @endif

                        @if($violation->shift)
                        <p class="text-sm text-gray-500 mt-2">
                            Related Shift: {{ $violation->shift->title ?? 'Shift #'.$violation->shift->id }}
                            ({{ \Carbon\Carbon::parse($violation->shift->shift_date)->format('M d, Y') }})
                        </p>
                        @endif

                        @if($violation->resolution_notes)
                        <div class="mt-2 p-2 bg-green-50 rounded text-sm text-green-800">
                            <strong>Resolution:</strong> {{ $violation->resolution_notes }}
                        </div>
                        @endif
                    </div>

                    <div class="ml-4 text-right">
                        <p class="text-sm text-gray-500">{{ $violation->created_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $violation->created_at->format('H:i') }}</p>

                        @if($violation->status === 'detected')
                        <form action="{{ route('worker.compliance.acknowledge-violation', $violation) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="text-sm text-blue-600 hover:underline">
                                Acknowledge
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4">
        {{ $violations->links() }}
    </div>
    @else
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <div class="text-gray-400 mb-4">
            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900">No Violations Found</h3>
        <p class="text-gray-500 mt-2">Great job! You have a clean compliance record.</p>
    </div>
    @endif
</div>
@endsection
