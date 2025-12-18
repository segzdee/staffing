@extends('layouts.dashboard')

@section('title', 'Certification Management')
@section('page-title', 'Certification Management')
@section('page-subtitle', 'SAF-003: Safety certifications overview and management')

@section('content')

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <x-dashboard.widget-card title="Pending Verifications">
            <div class="text-center">
                <div class="text-4xl font-bold {{ $pendingCount > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $pendingCount }}
                </div>
                <p class="text-sm text-gray-500 mt-1">Awaiting review</p>
                @if($pendingCount > 0)
                    <a href="{{ route('admin.certifications.pending') }}" class="inline-block mt-3 text-sm text-primary-600 hover:underline">
                        Review Now
                    </a>
                @endif
            </div>
        </x-dashboard.widget-card>

        <x-dashboard.widget-card title="Expiring Soon">
            <div class="text-center">
                <div class="text-4xl font-bold {{ $expiringCount > 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ $expiringCount }}
                </div>
                <p class="text-sm text-gray-500 mt-1">Next 30 days</p>
                @if($expiringCount > 0)
                    <a href="{{ route('admin.certifications.expiring') }}" class="inline-block mt-3 text-sm text-primary-600 hover:underline">
                        View All
                    </a>
                @endif
            </div>
        </x-dashboard.widget-card>

        <x-dashboard.widget-card title="Compliance Rate">
            <div class="text-center">
                <div class="text-4xl font-bold text-green-600">
                    {{ $complianceReport['compliance_rate'] ?? 0 }}%
                </div>
                <p class="text-sm text-gray-500 mt-1">Verified & valid</p>
            </div>
        </x-dashboard.widget-card>

        <x-dashboard.widget-card title="Total Verified">
            <div class="text-center">
                <div class="text-4xl font-bold text-gray-900">
                    {{ number_format($complianceReport['summary']['total_verified'] ?? 0) }}
                </div>
                <p class="text-sm text-gray-500 mt-1">Active certifications</p>
            </div>
        </x-dashboard.widget-card>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.certifications.pending') }}" class="inline-flex items-center px-4 py-2 bg-amber-100 text-amber-800 rounded-lg hover:bg-amber-200 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Review Pending ({{ $pendingCount }})
            </a>
            <a href="{{ route('admin.certifications.expiring') }}" class="inline-flex items-center px-4 py-2 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Expiring Certs ({{ $expiringCount }})
            </a>
            <a href="{{ route('admin.certifications.compliance') }}" class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-lg hover:bg-blue-200 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Compliance Report
            </a>
        </div>
    </div>

    <!-- Certification Types Table -->
    <x-dashboard.widget-card title="Safety Certification Types">
        <div class="flex justify-between items-center mb-4">
            <p class="text-sm text-gray-500">Manage certification types that workers can submit</p>
            <button type="button" class="inline-flex items-center px-3 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition" onclick="openAddCertificationModal()">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Certification Type
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mandatory</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($certificationTypes as $type)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $type->name }}</div>
                                <div class="text-xs text-gray-500">{{ $type->issuing_authority ?? 'Various' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @switch($type->category)
                                        @case('food_safety') bg-orange-100 text-orange-800 @break
                                        @case('health') bg-green-100 text-green-800 @break
                                        @case('security') bg-blue-100 text-blue-800 @break
                                        @case('industry_specific') bg-purple-100 text-purple-800 @break
                                        @default bg-gray-100 text-gray-800
                                    @endswitch
                                ">
                                    {{ $type->category_label }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $type->validity_display }}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($type->is_mandatory)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Required</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Optional</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                @if($type->is_active)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                <button type="button" class="text-primary-600 hover:text-primary-900 mr-3" onclick="editCertificationType({{ $type->id }})">Edit</button>
                                <button type="button" class="text-red-600 hover:text-red-900" onclick="toggleCertificationStatus({{ $type->id }}, {{ $type->is_active ? 'false' : 'true' }})">
                                    {{ $type->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No certification types found. Add one to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-dashboard.widget-card>

    <!-- Compliance Summary by Category -->
    @if(!empty($complianceReport['by_category']))
        <div class="mt-6">
            <x-dashboard.widget-card title="Compliance by Category">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    @foreach($complianceReport['by_category'] as $category => $data)
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="text-xs font-medium text-gray-500 uppercase mb-2">{{ str_replace('_', ' ', $category) }}</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $data['valid_worker_certifications'] }}</div>
                            <div class="text-xs text-gray-500">valid certifications</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $data['certifications'] }} type(s)</div>
                        </div>
                    @endforeach
                </div>
            </x-dashboard.widget-card>
        </div>
    @endif

@endsection

@push('scripts')
<script>
    function openAddCertificationModal() {
        // Implement modal or redirect to add page
        alert('Add Certification Type - Modal to be implemented');
    }

    function editCertificationType(id) {
        // Implement edit modal or redirect
        alert('Edit Certification Type #' + id + ' - Modal to be implemented');
    }

    function toggleCertificationStatus(id, activate) {
        if (confirm('Are you sure you want to ' + (activate ? 'activate' : 'deactivate') + ' this certification type?')) {
            // AJAX call to update status
            fetch('/api/admin/certifications/types/' + id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ is_active: activate })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
    }
</script>
@endpush
