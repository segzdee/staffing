@extends('layouts.dashboard')

@section('title', 'Data Residency Settings')
@section('page-title', 'Data Residency')
@section('page-subtitle', 'Manage where your data is stored')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
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

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Current Status --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Current Data Region</h3>
        </div>
        <div class="p-6">
            @if($residency)
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-lg bg-blue-100 text-blue-700 text-2xl font-bold uppercase">
                        {{ strtoupper($residency->dataRegion->code) }}
                    </span>
                    <div class="ml-6">
                        <p class="text-lg font-semibold text-gray-900">{{ $residency->dataRegion->name }}</p>
                        <p class="text-sm text-gray-500">Primary Storage: {{ $residency->dataRegion->primary_storage }}</p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($residency->dataRegion->compliance_frameworks ?? [] as $framework)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $framework }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-100">
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Detected Country</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ $residency->detected_country }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Selection Method</dt>
                            <dd class="text-sm text-gray-900 mt-1">
                                @if($residency->user_selected)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">
                                        You Selected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                        Auto-Assigned
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Consent Status</dt>
                            <dd class="text-sm text-gray-900 mt-1">
                                @if($residency->consent_given_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                        Given {{ $residency->consent_given_at->format('M j, Y') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">
                                        Pending
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                @if(!$residency->consent_given_at)
                    <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                        <div class="flex">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">Consent Required</p>
                                <p class="text-sm text-yellow-700 mt-1">Please provide your consent for data storage in your assigned region.</p>
                                <form method="POST" action="{{ route('settings.data-residency.consent') }}" class="mt-3">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 text-sm font-medium text-yellow-800 bg-yellow-100 rounded-lg hover:bg-yellow-200">
                                        Give Consent
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No Region Assigned</h3>
                    <p class="mt-2 text-gray-500">Please select a data region below to continue.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Change Region --}}
    @if($allowSelection)
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ $residency ? 'Change' : 'Select' }} Data Region</h3>
                <p class="text-sm text-gray-500 mt-1">Choose where your personal data will be stored.</p>
            </div>
            <form method="POST" action="{{ route('settings.data-residency.update') }}">
                @csrf
                <div class="p-6 space-y-6">
                    {{-- Region Selection --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($regions as $region)
                            <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:border-gray-400 transition-colors {{ $residency && $residency->data_region_id == $region->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                <input type="radio"
                                       name="data_region_id"
                                       value="{{ $region->id }}"
                                       class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
                                       {{ $residency && $residency->data_region_id == $region->id ? 'checked' : '' }}
                                       {{ old('data_region_id') == $region->id ? 'checked' : '' }}>
                                <div class="ml-3">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded bg-gray-100 text-xs font-bold text-gray-600 uppercase mr-2">
                                            {{ $region->code }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">{{ $region->name }}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">{{ count($region->countries ?? []) }} countries</p>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        @foreach($region->compliance_frameworks ?? [] as $framework)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-50 text-purple-700">
                                                {{ $framework }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('data_region_id')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- Consent Checkbox --}}
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <label class="flex items-start">
                            <input type="checkbox"
                                   name="consent"
                                   value="1"
                                   class="mt-1 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                   {{ old('consent') ? 'checked' : '' }}>
                            <span class="ml-3 text-sm text-gray-700">
                                I consent to having my personal data stored in the selected region. I understand that my data will be processed in accordance with the applicable data protection laws and the platform's privacy policy.
                            </span>
                        </label>
                        @error('consent')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($residency)
                        <div class="p-4 bg-amber-50 rounded-lg">
                            <div class="flex">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-amber-800">Data Migration Notice</p>
                                    <p class="text-sm text-amber-700 mt-1">
                                        Changing your data region will initiate a migration of your data to the new location. This process may take some time depending on the amount of data.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                        {{ $residency ? 'Update Region' : 'Select Region' }}
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">Region Selection Disabled</h3>
            <p class="mt-2 text-gray-500">Data region selection is managed by the platform. If you need to change your data region, please contact support.</p>
        </div>
    @endif

    {{-- Information --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">About Data Residency</h3>
        </div>
        <div class="p-6">
            <div class="prose prose-sm text-gray-600">
                <p>
                    Data residency ensures that your personal information is stored in a specific geographic location.
                    This is important for compliance with regional data protection laws such as GDPR, CCPA, and others.
                </p>
                <h4 class="text-gray-900 mt-4">Why does this matter?</h4>
                <ul class="mt-2 space-y-2">
                    <li>Your data is subject to the laws of the region where it is stored</li>
                    <li>Some regions have stronger data protection requirements</li>
                    <li>You may have specific rights depending on your location</li>
                    <li>Cross-border data transfers are regulated in many jurisdictions</li>
                </ul>
                <h4 class="text-gray-900 mt-4">Your Rights</h4>
                <ul class="mt-2 space-y-2">
                    <li>Access your personal data</li>
                    <li>Request correction of inaccurate data</li>
                    <li>Request deletion of your data</li>
                    <li>Export your data in a portable format</li>
                </ul>
                <p class="mt-4">
                    For more information about how we handle your data, please review our
                    <a href="{{ route('privacy') }}" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
