@extends('layouts.dashboard')

@section('title', 'User Distribution')
@section('page-title', 'User Distribution')
@section('page-subtitle', 'View user distribution across data regions')

@section('content')
<div class="space-y-6">
    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <form method="GET" action="{{ route('admin.data-residency.user-distribution') }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search User</label>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Name or email..."
                       class="w-full h-10 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                <select name="region" class="w-full h-10 px-3 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <option value="">All Regions</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->id }}" {{ $selectedRegion == $region->id ? 'selected' : '' }}>{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <input type="text" name="country" value="{{ $selectedCountry ?? '' }}" placeholder="e.g. US"
                       class="w-full h-10 px-3 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
            </div>
            <div class="w-40">
                <label class="block text-sm font-medium text-gray-700 mb-1">Consent</label>
                <select name="consent" class="w-full h-10 px-3 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <option value="">All</option>
                    <option value="given" {{ $selectedConsent === 'given' ? 'selected' : '' }}>Consent Given</option>
                    <option value="pending" {{ $selectedConsent === 'pending' ? 'selected' : '' }}>Consent Pending</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    Filter
                </button>
                <a href="{{ route('admin.data-residency.user-distribution') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Clear
                </a>
            </div>
        </form>
    </div>

    {{-- Back Button --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.data-residency.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            Back to Dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Country Distribution Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Top Countries</h3>
                </div>
                <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    @forelse($countryDistribution as $country)
                        <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 text-xs font-bold text-gray-600">
                                    {{ $country->detected_country }}
                                </span>
                                <a href="{{ route('admin.data-residency.user-distribution', ['country' => $country->detected_country]) }}"
                                   class="ml-3 text-sm text-gray-900 hover:text-blue-600">
                                    {{ $country->detected_country }}
                                </a>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ $country->count }}</span>
                        </div>
                    @empty
                        <div class="px-6 py-4 text-sm text-gray-500 text-center">
                            No country data available.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- User List --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Users ({{ $residencies->total() }})</h3>
                </div>
                @if($residencies->isEmpty())
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">No users found</h3>
                        <p class="mt-2 text-gray-500">Try adjusting your filters.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consent</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($residencies as $residency)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $residency->user->name ?? 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">{{ $residency->user->email ?? '' }}</div>
                                            @if($residency->user->role ?? null)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 mt-1">
                                                    {{ ucfirst($residency->user->role) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 uppercase">
                                                {{ $residency->dataRegion->code ?? 'N/A' }}
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">{{ $residency->dataRegion->name ?? '' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-900">{{ $residency->detected_country }}</span>
                                            @if($residency->user_selected)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 ml-1">
                                                    User Selected
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($residency->consent_given_at)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Given
                                                </span>
                                                <div class="text-xs text-gray-500 mt-1">{{ $residency->consent_given_at->format('M j, Y') }}</div>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $residency->created_at->format('M j, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('admin.data-residency.user-report', $residency->user) }}"
                                               class="text-blue-600 hover:text-blue-900">
                                                View Report
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($residencies->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200">
                            {{ $residencies->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
