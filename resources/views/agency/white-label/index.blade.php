@extends('layouts.agency')

@section('title', 'White-Label Settings')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">White-Label Portal</h1>
        <p class="text-gray-600 mt-1">Customize your branded worker portal with your own colors, logo, and domain.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('info'))
        <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
            {{ session('info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$hasConfig)
        {{-- Create New Configuration --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Create Your White-Label Portal</h2>
                <p class="text-gray-600 max-w-md mx-auto">Set up a branded portal for your workers with your own logo, colors, and custom domain.</p>
            </div>

            <form action="{{ route('agency.white-label.store') }}" method="POST" class="max-w-2xl mx-auto space-y-6">
                @csrf

                <div>
                    <label for="brand_name" class="block text-sm font-medium text-gray-700 mb-1">Brand Name *</label>
                    <input type="text" name="brand_name" id="brand_name" value="{{ old('brand_name', $agency->agencyProfile?->agency_name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Your Agency Name">
                </div>

                <div>
                    <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-1">Subdomain</label>
                    <div class="flex items-center">
                        <input type="text" name="subdomain" id="subdomain" value="{{ old('subdomain') }}"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="your-agency">
                        <span class="px-4 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-gray-600">
                            {{ config('whitelabel.default_subdomain_suffix', '.overtimestaff.com') }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Leave blank to auto-generate from your brand name</p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                        <input type="color" name="primary_color" id="primary_color" value="{{ old('primary_color', '#3B82F6') }}"
                            class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer">
                    </div>
                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
                        <input type="color" name="secondary_color" id="secondary_color" value="{{ old('secondary_color', '#1E40AF') }}"
                            class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer">
                    </div>
                    <div>
                        <label for="accent_color" class="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
                        <input type="color" name="accent_color" id="accent_color" value="{{ old('accent_color', '#10B981') }}"
                            class="w-full h-10 border border-gray-300 rounded-lg cursor-pointer">
                    </div>
                </div>

                <div>
                    <label for="support_email" class="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
                    <input type="email" name="support_email" id="support_email" value="{{ old('support_email', $agency->email) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="support@youragency.com">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        Create White-Label Portal
                    </button>
                </div>
            </form>
        </div>
    @else
        {{-- Existing Configuration --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Settings --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Portal Status --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Portal Status</h2>
                        <form action="{{ route('agency.white-label.toggle-status') }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $config->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                {{ $config->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </div>

                    <div class="space-y-3">
                        @if($config->subdomain)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Subdomain URL</span>
                                <a href="{{ $config->subdomain_url }}" target="_blank" class="text-blue-600 hover:underline">
                                    {{ $config->subdomain_url }}
                                </a>
                            </div>
                        @endif

                        @if($config->custom_domain)
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-600">Custom Domain</span>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $config->custom_domain_verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $config->custom_domain_verified ? 'Verified' : 'Pending' }}
                                    </span>
                                    <span class="text-gray-900">{{ $config->custom_domain }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Branding Settings --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Branding</h2>

                    <form action="{{ route('agency.white-label.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="brand_name" class="block text-sm font-medium text-gray-700 mb-1">Brand Name</label>
                            <input type="text" name="brand_name" id="brand_name" value="{{ old('brand_name', $config->brand_name) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="logo_url" class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
                            <input type="url" name="logo_url" id="logo_url" value="{{ old('logo_url', $config->logo_url) }}"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="https://...">
                            @if($config->logo_url)
                                <div class="mt-2">
                                    <img src="{{ $config->logo_url }}" alt="Current logo" class="h-12 object-contain">
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="edit_primary_color" class="block text-sm font-medium text-gray-700 mb-1">Primary</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="primary_color" id="edit_primary_color" value="{{ old('primary_color', $config->primary_color) }}"
                                        class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                    <input type="text" value="{{ $config->primary_color }}" readonly
                                        class="flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded text-sm text-gray-600">
                                </div>
                            </div>
                            <div>
                                <label for="edit_secondary_color" class="block text-sm font-medium text-gray-700 mb-1">Secondary</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="secondary_color" id="edit_secondary_color" value="{{ old('secondary_color', $config->secondary_color) }}"
                                        class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                    <input type="text" value="{{ $config->secondary_color }}" readonly
                                        class="flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded text-sm text-gray-600">
                                </div>
                            </div>
                            <div>
                                <label for="edit_accent_color" class="block text-sm font-medium text-gray-700 mb-1">Accent</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" name="accent_color" id="edit_accent_color" value="{{ old('accent_color', $config->accent_color) }}"
                                        class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                                    <input type="text" value="{{ $config->accent_color }}" readonly
                                        class="flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded text-sm text-gray-600">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="support_email" class="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
                                <input type="email" name="support_email" id="support_email" value="{{ old('support_email', $config->support_email) }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="support_phone" class="block text-sm font-medium text-gray-700 mb-1">Support Phone</label>
                                <input type="text" name="support_phone" id="support_phone" value="{{ old('support_phone', $config->support_phone) }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="hide_powered_by" value="1" {{ $config->hide_powered_by ? 'checked' : '' }}
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Hide "Powered by OvertimeStaff" in footer</span>
                            </label>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Custom CSS --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Custom CSS</h2>
                    <p class="text-sm text-gray-600 mb-4">Add custom CSS to further customize your portal's appearance. Maximum {{ number_format(config('whitelabel.max_custom_css_length', 50000)) }} characters.</p>

                    <form action="{{ route('agency.white-label.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="brand_name" value="{{ $config->brand_name }}">

                        <textarea name="custom_css" rows="10"
                            class="w-full px-4 py-3 font-mono text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="/* Your custom CSS here */&#10;.my-class { color: red; }">{{ old('custom_css', $config->custom_css) }}</textarea>

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Save CSS
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Domain Management --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Custom Domain</h2>

                    @if($config->custom_domain && $config->custom_domain_verified)
                        <div class="p-4 bg-green-50 rounded-lg mb-4">
                            <div class="flex items-center gap-2 text-green-800">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">{{ $config->custom_domain }}</span>
                            </div>
                            <p class="text-sm text-green-700 mt-1">Domain verified and active</p>
                        </div>
                        <form action="{{ route('agency.white-label.domain.remove') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors"
                                onclick="return confirm('Are you sure you want to remove this custom domain?')">
                                Remove Custom Domain
                            </button>
                        </form>
                    @elseif($config->custom_domain && !$config->custom_domain_verified)
                        <div class="p-4 bg-yellow-50 rounded-lg mb-4">
                            <div class="flex items-center gap-2 text-yellow-800">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                <span class="font-medium">{{ $config->custom_domain }}</span>
                            </div>
                            <p class="text-sm text-yellow-700 mt-1">Pending verification</p>
                        </div>
                        @php
                            $domainRecord = $config->domains()->where('domain', $config->custom_domain)->first();
                        @endphp
                        @if($domainRecord)
                            <a href="{{ route('agency.white-label.domain.verify', ['domain' => $domainRecord->id]) }}"
                                class="w-full inline-block text-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Complete Verification
                            </a>
                        @endif
                    @else
                        <form action="{{ route('agency.white-label.domain.setup') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="domain" class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
                                <input type="text" name="domain" id="domain" value="{{ old('domain') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="staffing.yourdomain.com">
                            </div>
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                Add Custom Domain
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Preview --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Preview</h2>
                    <a href="{{ route('agency.white-label.preview') }}" target="_blank"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors" rel="noopener noreferrer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Preview Portal
                    </a>
                </div>

                {{-- Danger Zone --}}
                <div class="bg-white rounded-xl shadow-sm border border-red-200 p-6">
                    <h2 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h2>
                    <p class="text-sm text-gray-600 mb-4">Permanently delete your white-label configuration. This action cannot be undone.</p>
                    <form action="{{ route('agency.white-label.destroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors"
                            onclick="return confirm('Are you sure you want to delete your white-label configuration? This cannot be undone.')">
                            Delete Configuration
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
