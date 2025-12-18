@extends('layouts.worker')

@section('title', 'Weekly Hours Summary')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('worker.compliance.index') }}" class="text-blue-600 hover:underline text-sm">
            &larr; Back to Compliance Dashboard
        </a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-6">Weekly Hours Summary</h1>

    <!-- Week Navigation -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('worker.compliance.weekly-hours', ['week' => $weekStart->copy()->subWeek()->format('Y-m-d')]) }}"
               class="text-blue-600 hover:underline">
                &larr; Previous Week
            </a>
            <span class="font-semibold text-gray-900">
                {{ $weekStart->format('M d') }} - {{ $weekStart->copy()->endOfWeek()->format('M d, Y') }}
            </span>
            @if($weekStart->copy()->addWeek()->startOfWeek()->lte(now()))
            <a href="{{ route('worker.compliance.weekly-hours', ['week' => $weekStart->copy()->addWeek()->format('Y-m-d')]) }}"
               class="text-blue-600 hover:underline">
                Next Week &rarr;
            </a>
            @else
            <span class="text-gray-400">Next Week &rarr;</span>
            @endif
        </div>
    </div>

    <!-- Hours Summary -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Hours Summary</h2>

        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">Hours Worked</span>
                <span class="font-medium">{{ number_format($weeklyHours, 1) }}h / {{ $weeklyLimit }}h</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4">
                @php
                    $percentage = min(100, ($weeklyHours / $weeklyLimit) * 100);
                    $colorClass = $percentage >= 100 ? 'bg-red-600' : ($percentage >= 80 ? 'bg-yellow-500' : 'bg-green-500');
                @endphp
                <div class="{{ $colorClass }} h-4 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
            </div>
        </div>

        @if($hasOptedOut)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4">
            <p class="text-sm text-blue-800">
                <strong>Note:</strong> You have opted out of the weekly hours limit.
                You may work more than {{ $weeklyLimit }} hours per week.
            </p>
        </div>
        @elseif($weeklyHours >= $weeklyLimit)
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-4">
            <p class="text-sm text-red-800">
                <strong>Warning:</strong> You have reached the weekly hours limit.
                Additional shifts may be blocked unless you have an opt-out.
            </p>
        </div>
        @elseif($weeklyHours >= $weeklyLimit * 0.8)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mt-4">
            <p class="text-sm text-yellow-800">
                <strong>Notice:</strong> You are approaching the weekly hours limit.
                {{ number_format($weeklyLimit - $weeklyHours, 1) }} hours remaining.
            </p>
        </div>
        @endif
    </div>

    <!-- Shifts This Week -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Shifts This Week</h2>

        @if(count($shifts) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($shifts as $assignment)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($assignment->shift->shift_date)->format('D, M d') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            {{ $assignment->shift->title ?? $assignment->shift->role_type }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                            {{ $assignment->shift->start_time }} - {{ $assignment->shift->end_time }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ number_format($assignment->hours_worked ?? $assignment->shift->duration_hours ?? 0, 1) }}h
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full {{
                                $assignment->status === 'completed' ? 'bg-green-100 text-green-800' :
                                ($assignment->status === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800')
                            }}">
                                {{ ucfirst($assignment->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900">Total</td>
                        <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ number_format($weeklyHours, 1) }}h</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <p class="text-gray-500 text-center py-8">No shifts scheduled for this week.</p>
        @endif
    </div>
</div>
@endsection
