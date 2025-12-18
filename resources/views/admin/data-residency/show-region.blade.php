@extends('layouts.dashboard')

@section('title', $region->name)
@section('page-title', $region->name)
@section('page-subtitle', 'Data region details and user assignments')

@section('content')
<div class="space-y-6">
    {{-- Back and Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.data-residency.regions') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Regions
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.data-residency.edit-region', $region) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Region
            </a>
            <form method="POST" action="{{ route('admin.data-residency.toggle-region', $region) }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium {{ $region->is_active ? 'text-red-700 bg-red-50 border-red-200 hover:bg-red-100' : 'text-green-700 bg-green-50 border-green-200 hover:bg-green-100' }} border rounded-lg">
                    {{ $region->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    {{-- Region Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <span class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-blue-100 text-blue-700 text-lg font-bold uppercase">
                    {{ strtoupper($region->code) }}
                </span>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Region Code</p>
                    <p class="text-lg font-bold text-gray-900">{{ $region->code }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Users</p>
                    <p class="text-lg font-bold text-gray-900">{{ $users->total() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Countries</p>
                    <p class="text-lg font-bold text-gray-900">{{ count($region->countries ?? []) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 {{ $region->is_active ? 'bg-green-100' : 'bg-gray-100' }} rounded-lg">
                    <svg class="w-6 h-6 {{ $region->is_active ? 'text-green-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Status</p>
                    <p class="text-lg font-bold {{ $region->is_active ? 'text-green-600' : 'text-gray-500' }}">
                        {{ $region->is_active ? 'Active' : 'Inactive' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Configuration --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Configuration</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Primary Storage</p>
                    <p class="text-sm text-gray-900 font-mono mt-1">{{ $region->primary_storage }}</p>
                </div>
                @if($region->backup_storage)
                    <div>
                        <p class="text-sm font-medium text-gray-500">Backup Storage</p>
                        <p class="text-sm text-gray-900 font-mono mt-1">{{ $region->backup_storage }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm font-medium text-gray-500">Compliance Frameworks</p>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach($region->compliance_frameworks ?? [] as $framework)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                {{ $framework }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Countries --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Countries ({{ count($region->countries ?? []) }})</h3>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
                    @foreach($region->countries ?? [] as $country)
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $country }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Transfer Stats --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transfer Statistics</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $transferStats['outgoing'] }}</p>
                        <p class="text-sm text-gray-500">Outgoing</p>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $transferStats['incoming'] }}</p>
                        <p class="text-sm text-gray-500">Incoming</p>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <p class="text-2xl font-bold text-yellow-900">{{ $transferStats['pending'] }}</p>
                        <p class="text-sm text-yellow-700">Pending</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Users in Region --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Users in This Region</h3>
        </div>
        @if($users->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No users in this region</h3>
                <p class="mt-2 text-gray-500">Users will appear here when assigned to this region.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Consent</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $residency)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $residency->user->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $residency->user->email ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ $residency->detected_country }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($residency->consent_given_at)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Given
                                        </span>
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

            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
