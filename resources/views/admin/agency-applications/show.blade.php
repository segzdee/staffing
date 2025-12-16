@extends('admin.layout')

@section('title', 'Review Application - ' . $application->agency_name)

@section('content')
<div class="space-y-6">
    <!-- Page Header with Actions -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.agency-applications.index') }}"
                   class="inline-flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900">{{ $application->agency_name }}</h1>
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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ $application->getStatusLabel() }}
                </span>
            </div>
            <p class="mt-1 text-sm text-gray-500">
                Application #{{ $application->id }} | Submitted {{ $application->submitted_at?->diffForHumans() ?? 'Not yet submitted' }}
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-3">
            @if(!$application->isTerminal())
                <!-- Approve Button -->
                @if($application->hasAllDocumentsVerified() && $application->hasAllComplianceChecksPassed())
                    <button type="button" data-hs-overlay="#approveModal"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve Application
                    </button>
                @endif

                <!-- Reject Button -->
                <button type="button" data-hs-overlay="#rejectModal"
                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Reject
                </button>

                <!-- Assign Reviewer -->
                <button type="button" data-hs-overlay="#assignModal"
                        class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ $application->reviewer ? 'Change Reviewer' : 'Assign Reviewer' }}
                </button>
            @endif

            <!-- Add Note -->
            <button type="button" data-hs-overlay="#noteModal"
                    class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Add Note
            </button>
        </div>
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

    <!-- Workflow Progress -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-sm font-medium text-gray-900 mb-4">Application Progress</h3>
        <div class="flex items-center justify-between">
            @php
                $currentStep = $application->getCurrentStep();
                $steps = [
                    1 => ['label' => 'Submitted', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    2 => ['label' => 'Documents', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    3 => ['label' => 'Compliance', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    4 => ['label' => 'Agreement', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                    5 => ['label' => 'Approved', 'icon' => 'M5 13l4 4L19 7'],
                ];
            @endphp

            @foreach($steps as $stepNum => $step)
                <div class="flex flex-col items-center relative {{ $stepNum < count($steps) ? 'flex-1' : '' }}">
                    <div class="flex items-center {{ $stepNum < count($steps) ? 'w-full' : '' }}">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors
                            {{ $stepNum < $currentStep ? 'bg-green-600 border-green-600 text-white' :
                               ($stepNum == $currentStep ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-300 text-gray-400') }}">
                            @if($stepNum < $currentStep)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $step['icon'] }}"/>
                                </svg>
                            @endif
                        </div>
                        @if($stepNum < count($steps))
                            <div class="flex-1 h-0.5 mx-2 {{ $stepNum < $currentStep ? 'bg-green-600' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                    <span class="mt-2 text-xs font-medium {{ $stepNum <= $currentStep ? 'text-gray-900' : 'text-gray-400' }}">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Company Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Company Information -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Company Information</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Agency Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->agency_name }}</dd>
                        </div>
                        @if($application->trading_name)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Trading Name</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $application->trading_name }}</dd>
                            </div>
                        @endif
                        @if($application->business_registration_number)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Registration Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $application->business_registration_number }}</dd>
                            </div>
                        @endif
                        @if($application->tax_id)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Tax ID</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $application->tax_id }}</dd>
                            </div>
                        @endif
                        @if($application->license_number)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">License Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $application->license_number }}</dd>
                            </div>
                        @endif
                        @if($application->business_type)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Business Type</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $application->business_type)) }}</dd>
                            </div>
                        @endif
                        @if($application->website)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Website</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <a href="{{ $application->website }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        {{ $application->website }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>

                    @if($application->description)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Business Description</dt>
                            <dd class="mt-2 text-sm text-gray-900">{{ $application->description }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Contact Information -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Contact Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->contact_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a href="mailto:{{ $application->contact_email }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $application->contact_email }}
                                </a>
                            </dd>
                        </div>
                        @if($application->contact_phone)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $application->contact_phone }}</dd>
                            </div>
                        @endif
                    </dl>

                    <!-- Address -->
                    @if($application->registered_address)
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Registered Address</dt>
                            <dd class="mt-2 text-sm text-gray-900">
                                {{ $application->registered_address }}<br>
                                {{ $application->registered_city }}{{ $application->registered_state ? ', ' . $application->registered_state : '' }}
                                {{ $application->registered_postal_code }}<br>
                                {{ $application->registered_country }}
                            </dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Documents Section -->
            @include('admin.agency-applications.partials.documents', ['application' => $application])

            <!-- Compliance Section -->
            @include('admin.agency-applications.partials.compliance', ['application' => $application, 'requiredChecks' => $requiredChecks, 'missingChecks' => $missingChecks])
        </div>

        <!-- Right Column - Meta Information -->
        <div class="space-y-6">
            <!-- Application Meta -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Application Details</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Application ID</dt>
                        <dd class="mt-1 text-sm text-gray-900">#{{ $application->id }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Applicant</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $application->user->name ?? 'Unknown' }}
                            <br>
                            <span class="text-gray-500">{{ $application->user->email ?? '' }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Assigned Reviewer</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($application->reviewer)
                                {{ $application->reviewer->name }}
                            @else
                                <span class="text-gray-400 italic">Not assigned</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Submitted</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if($application->submitted_at)
                                {{ $application->submitted_at->format('M j, Y g:i A') }}
                                <br>
                                <span class="text-gray-500">{{ $application->submitted_at->diffForHumans() }}</span>
                            @else
                                <span class="text-gray-400 italic">Not submitted</span>
                            @endif
                        </dd>
                    </div>
                    @if($application->estimated_worker_count)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Estimated Workers</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($application->estimated_worker_count) }}</dd>
                        </div>
                    @endif
                    @if($application->proposed_commission_rate)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Proposed Commission</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $application->proposed_commission_rate }}%</dd>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Specializations & Service Areas -->
            @if($application->specializations || $application->service_areas)
                <div class="bg-white rounded-lg border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Operations</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @if($application->specializations)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Specializations</dt>
                                <dd class="mt-2 flex flex-wrap gap-2">
                                    @foreach($application->specializations as $spec)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            {{ ucfirst($spec) }}
                                        </span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if($application->service_areas)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Service Areas</dt>
                                <dd class="mt-2 flex flex-wrap gap-2">
                                    @foreach($application->service_areas as $area)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                            {{ $area }}
                                        </span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Internal Notes -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Internal Notes</h3>
                </div>
                <div class="p-6">
                    @if($application->reviewer_notes)
                        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">{{ $application->reviewer_notes }}</div>
                    @else
                        <p class="text-sm text-gray-400 italic">No internal notes yet.</p>
                    @endif
                </div>
            </div>

            <!-- Rejection Info (if applicable) -->
            @if($application->isRejected())
                <div class="bg-red-50 rounded-lg border border-red-200">
                    <div class="px-6 py-4 border-b border-red-200">
                        <h3 class="text-lg font-medium text-red-800">Rejection Details</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-red-700">{{ $application->rejection_reason }}</p>
                        @if($application->rejected_at)
                            <p class="mt-4 text-xs text-red-600">
                                Rejected on {{ $application->rejected_at->format('M j, Y g:i A') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Approve Modal (Preline UI) -->
<div id="approveModal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <form method="POST" action="{{ route('admin.agency-applications.approve', $application->id) }}">
                @csrf
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-800">Approve Application</h3>
                <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#approveModal">
                    <span class="sr-only">Close</span>
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m18 6-12 12"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4 sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Are you sure you want to approve this application? This will create an agency profile for <strong>{{ $application->agency_name }}</strong>.
                                </p>
                                <div class="mt-4">
                                    <label for="approve_notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                                    <textarea name="notes" id="approve_notes" rows="3"
                                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-green-500 focus:border-green-500"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#approveModal">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 disabled:pointer-events-none">
                    Approve Application
                </button>
            </div>
        </form>
    </div>
</div>
<div class="hs-overlay-backdrop fixed inset-0 z-[60] bg-gray-900 bg-opacity-50"></div>

<!-- Reject Modal (Preline UI) -->
<div id="rejectModal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-800">Reject Application</h3>
                <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#rejectModal">
                    <span class="sr-only">Close</span>
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m18 6-12 12"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.agency-applications.reject', $application->id) }}">
                @csrf
                <div class="p-4 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Please provide a reason for rejecting this application. This will be sent to the applicant.
                                </p>
                                <div class="mt-4">
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700">Rejection Reason <span class="text-red-500">*</span></label>
                                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required minlength="20"
                                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-red-500 focus:border-red-500"
                                              placeholder="Please provide a detailed explanation..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#rejectModal">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 disabled:opacity-50 disabled:pointer-events-none">
                    Reject Application
                </button>
            </div>
        </form>
    </div>
</div>
<div class="hs-overlay-backdrop fixed inset-0 z-[60] bg-gray-900 bg-opacity-50"></div>

<!-- Assign Reviewer Modal (Preline UI) -->
<div id="assignModal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-800">Assign Reviewer</h3>
                <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#assignModal">
                    <span class="sr-only">Close</span>
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m18 6-12 12"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.agency-applications.assign', $application->id) }}">
                @csrf
                <div class="p-4 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="mt-4">
                                <label for="reviewer_id" class="block text-sm font-medium text-gray-700">Select Reviewer <span class="text-red-500">*</span></label>
                                <select name="reviewer_id" id="reviewer_id" required
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a reviewer...</option>
                                    @foreach($reviewers as $reviewer)
                                        <option value="{{ $reviewer->id }}" {{ $application->reviewer_id == $reviewer->id ? 'selected' : '' }}>
                                            {{ $reviewer->name }} ({{ $reviewer->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-4">
                                <label for="assign_notes" class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                                <textarea name="notes" id="assign_notes" rows="2"
                                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Any special instructions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#assignModal">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                    Assign Reviewer
                </button>
            </div>
        </form>
    </div>
</div>
<div class="hs-overlay-backdrop fixed inset-0 z-[60] bg-gray-900 bg-opacity-50"></div>

<!-- Add Note Modal (Preline UI) -->
<div id="noteModal" class="hs-overlay hidden size-full fixed top-0 start-0 z-[80] overflow-x-hidden overflow-y-auto">
    <div class="hs-overlay-open:mt-7 hs-overlay-open:opacity-100 hs-overlay-open:duration-500 mt-0 opacity-0 ease-out transition-all sm:max-w-lg sm:w-full m-3 sm:mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-800">Add Internal Note</h3>
                <button type="button" class="flex justify-center items-center size-7 text-sm font-semibold rounded-full border border-transparent text-gray-800 hover:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#noteModal">
                    <span class="sr-only">Close</span>
                    <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m18 6-12 12"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.agency-applications.note', $application->id) }}">
                @csrf
                <div class="p-4 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
                            <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="mt-4">
                                <label for="note" class="block text-sm font-medium text-gray-700">Note <span class="text-red-500">*</span></label>
                                <textarea name="note" id="note" rows="4" required
                                          class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-gray-500 focus:border-gray-500"
                                          placeholder="Enter your note..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none" data-hs-overlay="#noteModal">
                    Cancel
                </button>
                <button type="submit" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-gray-900 text-white hover:bg-gray-800 disabled:opacity-50 disabled:pointer-events-none">
                    Add Note
                </button>
            </div>
        </form>
    </div>
</div>
<div class="hs-overlay-backdrop fixed inset-0 z-[60] bg-gray-900 bg-opacity-50"></div>
@endsection

{{-- Preline UI handles modal functionality automatically --}}
@endsection
