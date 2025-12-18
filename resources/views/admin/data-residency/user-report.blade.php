@extends('layouts.dashboard')

@section('title', 'Data Residency Report - ' . ($targetUser->name ?? 'User'))
@section('page-title', 'Data Residency Report')
@section('page-subtitle', 'User data residency details and transfer history')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Back Button --}}
    <div>
        <a href="{{ route('admin.data-residency.user-distribution') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to User Distribution
        </a>
    </div>

    @if(!$report['has_residency'])
        {{-- No Residency Record --}}
        <div class="bg-yellow-50 rounded-xl border border-yellow-200 p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-yellow-900">No Data Residency Record</h3>
            <p class="mt-2 text-yellow-700">{{ $report['message'] }}</p>
        </div>
    @else
        {{-- User Header --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-600">{{ substr($targetUser->name ?? 'U', 0, 1) }}</span>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-xl font-bold text-gray-900">{{ $targetUser->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $targetUser->email }}</p>
                        <div class="flex items-center mt-2 gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 uppercase">
                                {{ $report['region']['code'] }}
                            </span>
                            <span class="text-sm text-gray-500">{{ $report['region']['name'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Assignment Details --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Assignment Details</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Detected Country</p>
                        <p class="text-sm text-gray-900 mt-1">{{ $report['assignment']['detected_country'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Selection Method</p>
                        <p class="text-sm text-gray-900 mt-1">
                            @if($report['assignment']['user_selected'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    User Selected
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Auto-Assigned
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Consent Status</p>
                        <p class="text-sm text-gray-900 mt-1">
                            @if($report['assignment']['consent_given_at'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Given
                                </span>
                                <span class="text-xs text-gray-500 ml-2">{{ \Carbon\Carbon::parse($report['assignment']['consent_given_at'])->format('M j, Y') }}</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Assigned At</p>
                        <p class="text-sm text-gray-900 mt-1">{{ \Carbon\Carbon::parse($report['assignment']['assigned_at'])->format('M j, Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Region Configuration</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Primary Storage</p>
                        <p class="text-sm text-gray-900 font-mono mt-1">{{ $report['region']['primary_storage'] }}</p>
                    </div>
                    @if($report['region']['backup_storage'])
                        <div>
                            <p class="text-sm font-medium text-gray-500">Backup Storage</p>
                            <p class="text-sm text-gray-900 font-mono mt-1">{{ $report['region']['backup_storage'] }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-sm font-medium text-gray-500">Compliance Frameworks</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($report['region']['compliance_frameworks'] ?? [] as $framework)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $framework }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Data Locations --}}
        @if($report['data_locations'])
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Data Locations</h3>
                </div>
                <div class="p-6">
                    <pre class="text-sm text-gray-700 whitespace-pre-wrap font-mono bg-gray-50 p-4 rounded-lg overflow-x-auto">{{ json_encode($report['data_locations'], JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        @endif

        {{-- Transfer History --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Transfer History</h3>
            </div>
            @if(empty($report['transfer_history']))
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No transfers</h3>
                    <p class="mt-2 text-gray-500">This user has no data transfer history.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($report['transfer_history'] as $transfer)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        #{{ $transfer['id'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 uppercase">
                                            {{ $transfer['from_region'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 uppercase">
                                            {{ $transfer['to_region'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ucfirst($transfer['type']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$transfer['status']] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $transfer['status'])) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($transfer['created_at'])->format('M j, Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Report Footer --}}
        <div class="text-center text-sm text-gray-500">
            Report generated at {{ \Carbon\Carbon::parse($report['generated_at'])->format('M j, Y H:i:s') }}
        </div>
    @endif
</div>
@endsection
