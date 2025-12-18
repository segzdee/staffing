@extends('layouts.dashboard')

@section('title', 'Edit ' . $region->name)
@section('page-title', 'Edit Region')
@section('page-subtitle', 'Update data region configuration')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <form method="POST" action="{{ route('admin.data-residency.update-region', $region) }}">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-6">
                {{-- Basic Information --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Region Code</label>
                            <div class="w-full h-10 px-4 flex items-center text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-lg">
                                {{ $region->code }}
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Code cannot be changed</p>
                        </div>
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Region Name *</label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $region->name) }}"
                                   placeholder="e.g. European Union"
                                   class="w-full h-10 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('name') border-red-500 @enderror"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Countries --}}
                <div>
                    <label for="countries" class="block text-sm font-medium text-gray-700 mb-1">Countries *</label>
                    <textarea id="countries"
                              name="countries"
                              rows="3"
                              placeholder="Enter comma-separated country codes: US, CA, MX"
                              class="w-full px-4 py-3 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('countries') border-red-500 @enderror"
                              required>{{ old('countries', implode(', ', $region->countries ?? [])) }}</textarea>
                    @error('countries')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">ISO 3166-1 alpha-2 country codes, comma-separated</p>
                </div>

                {{-- Storage Configuration --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Storage Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="primary_storage" class="block text-sm font-medium text-gray-700 mb-1">Primary Storage *</label>
                            <input type="text"
                                   id="primary_storage"
                                   name="primary_storage"
                                   value="{{ old('primary_storage', $region->primary_storage) }}"
                                   placeholder="e.g. s3-eu, s3-us"
                                   class="w-full h-10 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('primary_storage') border-red-500 @enderror"
                                   required>
                            @error('primary_storage')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Laravel filesystem disk name</p>
                        </div>
                        <div>
                            <label for="backup_storage" class="block text-sm font-medium text-gray-700 mb-1">Backup Storage</label>
                            <input type="text"
                                   id="backup_storage"
                                   name="backup_storage"
                                   value="{{ old('backup_storage', $region->backup_storage) }}"
                                   placeholder="e.g. s3-eu-backup"
                                   class="w-full h-10 px-4 text-sm text-gray-900 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent @error('backup_storage') border-red-500 @enderror">
                            @error('backup_storage')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Compliance Frameworks --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Compliance Frameworks *</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($complianceFrameworks as $code => $label)
                            <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                                <input type="checkbox"
                                       name="compliance_frameworks[]"
                                       value="{{ $code }}"
                                       class="w-4 h-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900"
                                       {{ in_array($code, old('compliance_frameworks', $region->compliance_frameworks ?? [])) ? 'checked' : '' }}>
                                <span class="ml-3 text-sm text-gray-700">{{ $code }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('compliance_frameworks')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Active Status --}}
                <div>
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               class="w-4 h-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900"
                               {{ old('is_active', $region->is_active) ? 'checked' : '' }}>
                        <span class="ml-3 text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500 ml-7">Inactive regions won't accept new user assignments</p>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="{{ route('admin.data-residency.regions') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                    Update Region
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
