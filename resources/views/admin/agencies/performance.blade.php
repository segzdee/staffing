@extends('admin.layout')

@section('title', 'Agency Performance Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Agency Performance Dashboard</h1>
        <p class="mt-2 text-gray-600">Monitor agency performance metrics and compliance</p>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Total Agencies</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $totalAgencies }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Green Status</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">{{ $greenCount }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">{{ round(($greenCount / max($totalAgencies, 1)) * 100, 1) }}% of agencies</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Yellow Status</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-2">{{ $yellowCount }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Needs attention</p>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium uppercase tracking-wide">Red Status</p>
                    <p class="text-2xl font-bold text-red-600 mt-2">{{ $redCount }}</p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">Critical issues</p>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <a href="{{ url('/admin/agencies/performance?status=all') }}"
                   class="border-{{ $filterStatus === 'all' ? 'blue' : 'transparent' }}-500 text-{{ $filterStatus === 'all' ? 'blue' : 'gray' }}-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    All Agencies
                </a>
                <a href="{{ url('/admin/agencies/performance?status=red') }}"
                   class="border-{{ $filterStatus === 'red' ? 'red' : 'transparent' }}-500 text-{{ $filterStatus === 'red' ? 'red' : 'gray' }}-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Critical ({{ $redCount }})
                </a>
                <a href="{{ url('/admin/agencies/performance?status=yellow') }}"
                   class="border-{{ $filterStatus === 'yellow' ? 'yellow' : 'transparent' }}-500 text-{{ $filterStatus === 'yellow' ? 'yellow' : 'gray' }}-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Warning ({{ $yellowCount }})
                </a>
                <a href="{{ url('/admin/agencies/performance?status=green') }}"
                   class="border-{{ $filterStatus === 'green' ? 'green' : 'transparent' }}-500 text-{{ $filterStatus === 'green' ? 'green' : 'gray' }}-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Healthy ({{ $greenCount }})
                </a>
            </nav>
        </div>
    </div>

    {{-- Performance Scorecards Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fill Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No-Show Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Rating</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Complaints</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($scorecards as $scorecard)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-purple-500 flex items-center justify-center text-white font-semibold">
                                        {{ substr($scorecard->agency->agencyProfile->agency_name ?? 'AG', 0, 2) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $scorecard->agency->agencyProfile->agency_name ?? 'Unknown Agency' }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $scorecard->total_shifts_assigned }} shifts this period
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($scorecard->status === 'green')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Healthy
                                </span>
                            @elseif($scorecard->status === 'yellow')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Warning
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Critical
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($scorecard->fill_rate, 1) }}%</div>
                            <div class="text-xs text-gray-500">Target: {{ number_format($scorecard->target_fill_rate, 0) }}%</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($scorecard->no_show_rate, 1) }}%</div>
                            <div class="text-xs text-gray-500">Target: &lt;{{ number_format($scorecard->target_no_show_rate, 0) }}%</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($scorecard->average_worker_rating, 2) }}</div>
                            <div class="text-xs text-gray-500">Target: &gt;{{ number_format($scorecard->target_average_rating, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($scorecard->complaint_rate, 1) }}%</div>
                            <div class="text-xs text-gray-500">{{ $scorecard->complaints_received }} complaints</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $scorecard->period_start->format('M j') }} - {{ $scorecard->period_end->format('M j') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ url('/admin/agencies/'.$scorecard->agency_id.'/scorecard/'.$scorecard->id) }}"
                               class="text-blue-600 hover:text-blue-900">
                                View Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No scorecard data available
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($scorecards->hasPages())
    <div class="mt-6">
        {{ $scorecards->links() }}
    </div>
    @endif

    {{-- Performance Targets Info --}}
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Performance Targets</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-blue-800 font-medium">Fill Rate: &gt;90%</p>
                <p class="text-xs text-blue-600">Percentage of shifts successfully filled</p>
            </div>
            <div>
                <p class="text-sm text-blue-800 font-medium">No-Show Rate: &lt;3%</p>
                <p class="text-xs text-blue-600">Workers failing to show up for confirmed shifts</p>
            </div>
            <div>
                <p class="text-sm text-blue-800 font-medium">Average Rating: &gt;4.3</p>
                <p class="text-xs text-blue-600">Average rating of agency-managed workers</p>
            </div>
            <div>
                <p class="text-sm text-blue-800 font-medium">Complaint Rate: &lt;2%</p>
                <p class="text-xs text-blue-600">Percentage of shifts with complaints</p>
            </div>
        </div>
    </div>

    {{-- Sanctions Info --}}
    @if($redCount > 0)
    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-red-900 mb-2">Automated Consequences</h3>
        <ul class="text-sm text-red-800 space-y-1">
            <li>1 Red Scorecard: Warning notification sent to agency</li>
            <li>2 Consecutive Red Scorecards: Commission rate increased by 2%</li>
            <li>3 Consecutive Red Scorecards: Agency suspended from platform</li>
        </ul>
    </div>
    @endif
</div>
@endsection
