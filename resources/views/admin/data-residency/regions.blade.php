@extends('layouts.dashboard')

@section('title', 'Data Regions')
@section('page-title', 'Data Regions')
@section('page-subtitle', 'Manage geographic data storage regions')

@section('content')
<div class="space-y-6">
    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            {{-- Search --}}
            <form method="GET" action="{{ route('admin.data-residency.regions') }}" class="flex items-center gap-2">
                @if($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $search ?? '' }}"
                           placeholder="Search regions..."
                           class="w-64 h-10 pl-10 pr-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Search
                </button>
            </form>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.data-residency.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                Back to Dashboard
            </a>
            <a href="{{ route('admin.data-residency.create-region') }}" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Region
            </a>
        </div>
    </div>

    {{-- Status Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="flex gap-4" aria-label="Status tabs">
            <a href="{{ route('admin.data-residency.regions', ['search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ !$status ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                All
            </a>
            <a href="{{ route('admin.data-residency.regions', ['status' => 'active', 'search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $status === 'active' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Active
            </a>
            <a href="{{ route('admin.data-residency.regions', ['status' => 'inactive', 'search' => $search]) }}"
               class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $status === 'inactive' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                Inactive
            </a>
        </nav>
    </div>

    {{-- Regions Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @if($regions->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No regions found</h3>
                <p class="mt-2 text-gray-500">Get started by creating a new data region.</p>
                <a href="{{ route('admin.data-residency.create-region') }}" class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    Create Region
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Countries</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Storage</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($regions as $region)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 text-blue-700 text-sm font-bold uppercase">
                                            {{ strtoupper($region->code) }}
                                        </span>
                                        <div class="ml-3">
                                            <a href="{{ route('admin.data-residency.show-region', $region) }}" class="text-sm font-medium text-gray-900 hover:text-gray-700">
                                                {{ $region->name }}
                                            </a>
                                            <p class="text-xs text-gray-500 font-mono">{{ $region->code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900">{{ count($region->countries ?? []) }} countries</span>
                                    <p class="text-xs text-gray-500">{{ implode(', ', array_slice($region->countries ?? [], 0, 5)) }}{{ count($region->countries ?? []) > 5 ? '...' : '' }}</p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $region->primary_storage }}</div>
                                    @if($region->backup_storage)
                                        <div class="text-xs text-gray-500">Backup: {{ $region->backup_storage }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($region->compliance_frameworks ?? [] as $framework)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                                                {{ $framework }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-semibold text-gray-900">{{ $region->user_data_residencies_count }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($region->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.data-residency.show-region', $region) }}"
                                           class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                                           title="View">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.data-residency.edit-region', $region) }}"
                                           class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100"
                                           title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.data-residency.toggle-region', $region) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                    class="p-2 rounded-lg transition-colors {{ $region->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}"
                                                    title="{{ $region->is_active ? 'Deactivate' : 'Activate' }}">
                                                @if($region->is_active)
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17 7H7a5 5 0 000 10h10a5 5 0 000-10zm0 8a3 3 0 110-6 3 3 0 010 6z"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M7 7h10a5 5 0 010 10H7A5 5 0 017 7zm0 8a3 3 0 100-6 3 3 0 000 6z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($regions->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $regions->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
