@extends('layouts.admin')

@section('title', 'Labor Law Compliance Dashboard')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Labor Law Compliance Dashboard</h1>
        <a href="{{ route('admin.labor-law.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Manage Rules
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Rules Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Labor Law Rules</h3>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['rules']['total'] }}</div>
                    <div class="text-xs text-gray-500">Total</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ $stats['rules']['active'] }}</div>
                    <div class="text-xs text-gray-500">Active</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-red-600">{{ $stats['rules']['blocking'] }}</div>
                    <div class="text-xs text-gray-500">Blocking</div>
                </div>
            </div>
        </div>

        <!-- Violations Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Violations</h3>
            <div class="grid grid-cols-2 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold {{ $stats['violations']['critical'] > 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $stats['violations']['critical'] }}
                    </div>
                    <div class="text-xs text-gray-500">Critical Unresolved</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['violations']['unresolved'] }}</div>
                    <div class="text-xs text-gray-500">Total Unresolved</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['violations']['this_month'] }}</div>
                    <div class="text-xs text-gray-500">This Month</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-400">{{ $stats['violations']['total'] }}</div>
                    <div class="text-xs text-gray-500">All Time</div>
                </div>
            </div>
            <a href="{{ route('admin.labor-law.violations') }}" class="text-blue-600 text-sm hover:underline mt-4 inline-block">
                View all violations
            </a>
        </div>

        <!-- Exemptions Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Worker Exemptions</h3>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold {{ $stats['exemptions']['pending'] > 0 ? 'text-yellow-600' : 'text-gray-900' }}">
                        {{ $stats['exemptions']['pending'] }}
                    </div>
                    <div class="text-xs text-gray-500">Pending</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ $stats['exemptions']['active'] }}</div>
                    <div class="text-xs text-gray-500">Active</div>
                </div>
                <div>
                    <div class="text-2xl font-bold {{ $stats['exemptions']['expiring_soon'] > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                        {{ $stats['exemptions']['expiring_soon'] }}
                    </div>
                    <div class="text-xs text-gray-500">Expiring Soon</div>
                </div>
            </div>
            <a href="{{ route('admin.labor-law.exemptions') }}" class="text-blue-600 text-sm hover:underline mt-4 inline-block">
                View all exemptions
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Violations -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Recent Violations</h3>
                <a href="{{ route('admin.labor-law.violations') }}" class="text-blue-600 text-sm hover:underline">View all</a>
            </div>

            @if($recentViolations->count() > 0)
            <div class="space-y-4">
                @foreach($recentViolations as $violation)
                <div class="flex items-start justify-between border-b pb-3 last:border-b-0">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 text-xs rounded-full {{
                                $violation->severity === 'critical' ? 'bg-red-100 text-red-800' :
                                ($violation->severity === 'violation' ? 'bg-orange-100 text-orange-800' : 'bg-yellow-100 text-yellow-800')
                            }}">
                                {{ ucfirst($violation->severity) }}
                            </span>
                            @if($violation->was_blocked)
                            <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">Blocked</span>
                            @endif
                        </div>
                        <p class="text-sm font-medium text-gray-900 mt-1">{{ $violation->user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $violation->laborLawRule->name }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">{{ $violation->created_at->diffForHumans() }}</p>
                        <a href="{{ route('admin.labor-law.violation', $violation) }}" class="text-blue-600 text-xs hover:underline">View</a>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-4">No recent violations.</p>
            @endif
        </div>

        <!-- Pending Exemptions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">Pending Exemption Requests</h3>
                <a href="{{ route('admin.labor-law.exemptions', ['status' => 'pending']) }}" class="text-blue-600 text-sm hover:underline">View all</a>
            </div>

            @if($pendingExemptions->count() > 0)
            <div class="space-y-4">
                @foreach($pendingExemptions as $exemption)
                <div class="flex items-start justify-between border-b pb-3 last:border-b-0">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $exemption->user->name }}</p>
                        <p class="text-xs text-gray-600">{{ $exemption->laborLawRule->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ Str::limit($exemption->reason, 50) }}</p>
                    </div>
                    <div class="text-right ml-4">
                        <p class="text-xs text-gray-500">{{ $exemption->created_at->diffForHumans() }}</p>
                        <div class="flex gap-2 mt-1">
                            <form action="{{ route('admin.labor-law.exemption.approve', $exemption) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 text-xs hover:underline">Approve</button>
                            </form>
                            <a href="{{ route('admin.labor-law.exemption', $exemption) }}" class="text-blue-600 text-xs hover:underline">Review</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500 text-center py-4">No pending exemption requests.</p>
            @endif
        </div>
    </div>

    <!-- Violations by Rule Type -->
    @if($violationsByType->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-700 mb-4">Violations by Rule Type</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
            @php
                $typeLabels = [
                    'working_time' => 'Working Time',
                    'rest_period' => 'Rest Period',
                    'break' => 'Break',
                    'overtime' => 'Overtime',
                    'age_restriction' => 'Age Restriction',
                    'wage' => 'Wage',
                    'night_work' => 'Night Work',
                ];
            @endphp
            @foreach($typeLabels as $type => $label)
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-xl font-bold text-gray-900">{{ $violationsByType[$type] ?? 0 }}</div>
                <div class="text-xs text-gray-500">{{ $label }}</div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
