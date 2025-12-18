@extends('layouts.worker')

@section('title', 'Compliance Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Compliance Dashboard</h1>

    <!-- Compliance Score -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Your Compliance Score</h2>
                <p class="text-sm text-gray-500">Based on your work history and violations</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold {{ $report['compliance_score'] >= 80 ? 'text-green-600' : ($report['compliance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $report['compliance_score'] }}
                </div>
                <div class="text-sm text-gray-500">out of 100</div>
            </div>
        </div>
    </div>

    <!-- Weekly Hours -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Weekly Hours</h2>
        <div class="flex items-center mb-4">
            <div class="flex-1 bg-gray-200 rounded-full h-4 mr-4">
                <div class="bg-blue-600 h-4 rounded-full"
                     style="width: {{ min(100, ($report['current_weekly_hours'] / $report['weekly_hours_limit']) * 100) }}%">
                </div>
            </div>
            <span class="text-sm font-medium text-gray-700">
                {{ number_format($report['current_weekly_hours'], 1) }} / {{ $report['weekly_hours_limit'] }}h
            </span>
        </div>
        <p class="text-sm text-gray-500">
            You have <strong>{{ number_format($report['weekly_hours_remaining'], 1) }} hours</strong> remaining this week.
        </p>
        <a href="{{ route('worker.compliance.weekly-hours') }}" class="text-blue-600 text-sm hover:underline mt-2 inline-block">
            View detailed breakdown
        </a>
    </div>

    <!-- Active Exemptions -->
    @if(count($exemptions) > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Your Active Opt-Outs</h2>
        <div class="space-y-4">
            @foreach($exemptions as $exemption)
            <div class="flex items-center justify-between border-b pb-4 last:border-b-0 last:pb-0">
                <div>
                    <h3 class="font-medium text-gray-900">{{ $exemption->laborLawRule->name }}</h3>
                    <p class="text-sm text-gray-500">
                        Valid from {{ $exemption->valid_from->format('M d, Y') }}
                        @if($exemption->valid_until)
                            until {{ $exemption->valid_until->format('M d, Y') }}
                        @else
                            (indefinite)
                        @endif
                    </p>
                </div>
                <form action="{{ route('worker.compliance.withdraw-opt-out', $exemption) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to withdraw this opt-out?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 text-sm hover:underline">
                        Withdraw
                    </button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Available Opt-Outs -->
    @if(count($optOutRules) > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Available Opt-Outs</h2>
        <p class="text-sm text-gray-500 mb-4">
            Some labor regulations allow workers to opt-out under specific conditions. Review carefully before opting out.
        </p>
        <div class="space-y-4">
            @foreach($optOutRules as $rule)
                @php
                    $hasExemption = $exemptions->where('labor_law_rule_id', $rule->id)->first();
                @endphp
                @if(!$hasExemption)
                <div class="border rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900">{{ $rule->name }}</h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $rule->description }}</p>
                            @if($rule->opt_out_requirements)
                            <p class="text-sm text-yellow-600 mt-2">
                                <span class="font-medium">Requirements:</span> {{ $rule->opt_out_requirements }}
                            </p>
                            @endif
                            @if($rule->legal_reference)
                            <p class="text-xs text-gray-400 mt-1">Ref: {{ $rule->legal_reference }}</p>
                            @endif
                        </div>
                        <a href="{{ route('worker.compliance.opt-out-form', $rule) }}"
                           class="ml-4 px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                            Request Opt-Out
                        </a>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    <!-- Recent Violations -->
    @if(count($report['recent_violations']) > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Recent Violations</h2>
            <a href="{{ route('worker.compliance.violations') }}" class="text-blue-600 text-sm hover:underline">
                View all
            </a>
        </div>
        <div class="space-y-4">
            @foreach($report['recent_violations'] as $violation)
            <div class="border-l-4 pl-4 {{
                $violation['severity'] === 'critical' ? 'border-red-500' :
                ($violation['severity'] === 'violation' ? 'border-orange-500' :
                ($violation['severity'] === 'warning' ? 'border-yellow-500' : 'border-blue-500'))
            }}">
                <div class="flex items-center justify-between">
                    <h3 class="font-medium text-gray-900">{{ $violation['rule_code'] }}</h3>
                    <span class="text-xs px-2 py-1 rounded-full {{
                        $violation['status'] === 'resolved' || $violation['status'] === 'exempted'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-yellow-100 text-yellow-800'
                    }}">
                        {{ ucfirst($violation['status']) }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mt-1">{{ $violation['description'] }}</p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ \Carbon\Carbon::parse($violation['created_at'])->diffForHumans() }}
                    @if($violation['was_blocked'])
                        <span class="text-red-500 ml-2">Action was blocked</span>
                    @endif
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Monthly Statistics -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">This Month</h2>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-2xl font-bold text-gray-900">{{ $report['monthly_stats']['shifts_completed'] }}</div>
                <div class="text-sm text-gray-500">Shifts Completed</div>
            </div>
            <div>
                <div class="text-2xl font-bold text-gray-900">{{ number_format($report['monthly_stats']['total_hours'], 1) }}</div>
                <div class="text-sm text-gray-500">Hours Worked</div>
            </div>
            <div>
                <div class="text-2xl font-bold {{ $report['monthly_stats']['violations_count'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $report['monthly_stats']['violations_count'] }}
                </div>
                <div class="text-sm text-gray-500">Violations</div>
            </div>
        </div>
    </div>
</div>
@endsection
