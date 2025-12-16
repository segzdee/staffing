@extends('admin.layout')

@section('title', 'Agency Applications')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Agency Applications</h1>
            <p class="mt-1 text-sm text-gray-500">Review and manage agency registration applications</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">
                {{ $applications->total() }} {{ Str::plural('application', $applications->total()) }}
            </span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-900">{{ $statistics['total'] }}</div>
            <div class="text-xs text-gray-500">Total</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $statistics['submitted'] }}</div>
            <div class="text-xs text-gray-500">Submitted</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-orange-600">{{ $statistics['documents_pending'] }}</div>
            <div class="text-xs text-gray-500">Docs Pending</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-purple-600">{{ $statistics['compliance_pending'] }}</div>
            <div class="text-xs text-gray-500">Compliance</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-green-600">{{ $statistics['approved_this_month'] }}</div>
            <div class="text-xs text-gray-500">Approved (Month)</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-red-600">{{ $statistics['rejected_this_month'] }}</div>
            <div class="text-xs text-gray-500">Rejected (Month)</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ $statistics['pending'] }}</div>
            <div class="text-xs text-gray-500">In Progress</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="text-2xl font-bold text-gray-600">{{ $statistics['unassigned'] }}</div>
            <div class="text-xs text-gray-500">Unassigned</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="{{ route('admin.agency-applications.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Company name, email, registration #...">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Statuses</option>
                        @foreach(\App\Models\AgencyApplication::STATUSES as $status)
                            <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Reviewer -->
                <div>
                    <label for="reviewer_id" class="block text-sm font-medium text-gray-700 mb-1">Reviewer</label>
                    <select name="reviewer_id" id="reviewer_id"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Reviewers</option>
                        <option value="unassigned" {{ ($filters['reviewer_id'] ?? '') === 'unassigned' ? 'selected' : '' }}>
                            Unassigned
                        </option>
                        @foreach($reviewers as $reviewer)
                            <option value="{{ $reviewer->id }}" {{ ($filters['reviewer_id'] ?? '') == $reviewer->id ? 'selected' : '' }}>
                                {{ $reviewer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Country -->
                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <select name="country" id="country"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ ($filters['country'] ?? '') === $country ? 'selected' : '' }}>
                                {{ $country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                <a href="{{ route('admin.agency-applications.index') }}" class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-green-700">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <span class="text-red-700">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Applications Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('admin.agency-applications.index', array_merge($filters, ['sort' => 'agency_name', 'dir' => ($filters['sort'] ?? '') === 'agency_name' && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc'])) }}"
                               class="flex items-center gap-1 hover:text-gray-700">
                                Agency
                                @if(($filters['sort'] ?? '') === 'agency_name')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="{{ ($filters['dir'] ?? '') === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contact
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('admin.agency-applications.index', array_merge($filters, ['sort' => 'status', 'dir' => ($filters['sort'] ?? '') === 'status' && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc'])) }}"
                               class="flex items-center gap-1 hover:text-gray-700">
                                Status
                                @if(($filters['sort'] ?? '') === 'status')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="{{ ($filters['dir'] ?? '') === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Progress
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Reviewer
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('admin.agency-applications.index', array_merge($filters, ['sort' => 'submitted_at', 'dir' => ($filters['sort'] ?? '') === 'submitted_at' && ($filters['dir'] ?? '') === 'asc' ? 'desc' : 'asc'])) }}"
                               class="flex items-center gap-1 hover:text-gray-700">
                                Submitted
                                @if(($filters['sort'] ?? 'submitted_at') === 'submitted_at')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="{{ ($filters['dir'] ?? 'desc') === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($applications as $application)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                        <span class="text-sm font-semibold text-gray-600">
                                            {{ strtoupper(substr($application->agency_name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $application->agency_name }}
                                        </div>
                                        @if($application->trading_name)
                                            <div class="text-sm text-gray-500">
                                                t/a {{ $application->trading_name }}
                                            </div>
                                        @endif
                                        @if($application->registered_country)
                                            <div class="text-xs text-gray-400">
                                                {{ $application->registered_city ?? '' }}{{ $application->registered_city && $application->registered_country ? ', ' : '' }}{{ $application->registered_country }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $application->contact_name }}</div>
                                <div class="text-sm text-gray-500">{{ $application->contact_email }}</div>
                                @if($application->contact_phone)
                                    <div class="text-xs text-gray-400">{{ $application->contact_phone }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'submitted' => 'bg-blue-100 text-blue-700',
                                        'pending_documents' => 'bg-orange-100 text-orange-700',
                                        'documents_verified' => 'bg-teal-100 text-teal-700',
                                        'pending_compliance' => 'bg-purple-100 text-purple-700',
                                        'compliance_approved' => 'bg-indigo-100 text-indigo-700',
                                        'pending_agreement' => 'bg-yellow-100 text-yellow-700',
                                        'approved' => 'bg-green-100 text-green-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        'withdrawn' => 'bg-gray-100 text-gray-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $application->getStatusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <!-- Documents Progress -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-12">Docs</span>
                                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 w-20">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $application->getDocumentCompletionPercentage() }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 w-8">{{ $application->getDocumentCompletionPercentage() }}%</span>
                                    </div>
                                    <!-- Compliance Progress -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500 w-12">Compl</span>
                                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 w-20">
                                            <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $application->getComplianceCompletionPercentage() }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500 w-8">{{ $application->getComplianceCompletionPercentage() }}%</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($application->reviewer)
                                    <div class="flex items-center">
                                        <div class="h-6 w-6 rounded-full bg-gray-900 flex items-center justify-center text-white text-xs font-medium">
                                            {{ strtoupper(substr($application->reviewer->name, 0, 1)) }}
                                        </div>
                                        <span class="ml-2 text-sm text-gray-700">{{ $application->reviewer->name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400 italic">Unassigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($application->submitted_at)
                                    <div>{{ $application->submitted_at->format('M j, Y') }}</div>
                                    <div class="text-xs text-gray-400">{{ $application->submitted_at->diffForHumans() }}</div>
                                @else
                                    <span class="text-gray-400">Not submitted</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.agency-applications.show', $application->id) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                                    Review
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No applications found</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    @if(!empty(array_filter($filters)))
                                        Try adjusting your filters to find what you're looking for.
                                    @else
                                        No agency applications have been submitted yet.
                                    @endif
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($applications->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
