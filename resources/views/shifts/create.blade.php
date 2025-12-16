@extends('layouts.authenticated')

@section('title', 'Post a Shift')
@section('page-title', 'Post a New Shift')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.shifts.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <span>My Shifts</span>
</a>
<a href="{{ route('shifts.create') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    <span>Post Shift</span>
</a>
@endsection

@push('styles')
<style>
    /* Form validation styles for shift creation */
    .form-field-error {
        border-color: #EF4444 !important;
    }
    .form-field-error:focus {
        border-color: #EF4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    .form-field-valid {
        border-color: #10B981 !important;
    }
    .form-field-valid:focus {
        border-color: #10B981 !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }
    .field-error-message {
        display: flex;
        align-items: center;
        gap: 4px;
        color: #EF4444;
        font-size: 0.75rem;
        margin-top: 4px;
    }
    .field-error-message svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
    }
    .validation-summary {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
    }
    .validation-summary-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #DC2626;
        font-weight: 600;
        margin-bottom: 8px;
    }
    .validation-summary-list {
        list-style: disc;
        padding-left: 20px;
        color: #DC2626;
        font-size: 0.875rem;
    }
    .validation-summary-list li {
        margin-bottom: 4px;
    }
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
        20%, 40%, 60%, 80% { transform: translateX(4px); }
    }
    .shake {
        animation: shake 0.4s ease-in-out;
    }
    .btn-loading {
        position: relative;
        color: transparent !important;
    }
    .btn-loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Accessibility: Live region for announcing validation errors -->
        <div id="validation-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-brand-600 text-white rounded-full font-semibold">1</div>
                    <span class="ml-3 text-sm font-medium text-gray-900">Shift Details</span>
                </div>
                <div class="flex-1 h-1 mx-4 bg-gray-200"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gray-200 text-gray-600 rounded-full font-semibold">2</div>
                    <span class="ml-3 text-sm font-medium text-gray-500">Requirements</span>
                </div>
                <div class="flex-1 h-1 mx-4 bg-gray-200"></div>
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gray-200 text-gray-600 rounded-full font-semibold">3</div>
                    <span class="ml-3 text-sm font-medium text-gray-500">Review</span>
                </div>
            </div>
        </div>

        <form action="{{ route('shifts.store') }}"
              method="POST"
              class="bg-white rounded-xl border border-gray-200 p-8 space-y-8"
              x-data="shiftForm()"
              x-init="if (form.venue_id) { handleVenueSelection(); }"
              @submit="handleSubmit"
              novalidate>
            @csrf

            <!-- Validation Summary (shown when there are errors on submit) -->
            <div x-show="showValidationSummary && Object.keys(errors).length > 0"
                 x-transition
                 class="validation-summary"
                 role="alert"
                 aria-labelledby="validation-summary-title">
                <div class="validation-summary-title" id="validation-summary-title">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Please correct the following errors:
                </div>
                <ul class="validation-summary-list">
                    <template x-for="(error, field) in errors" :key="field">
                        <li x-text="error"></li>
                    </template>
                </ul>
            </div>

            <!-- Server-side errors -->
            @if ($errors->any())
            <div class="validation-summary" role="alert">
                <div class="validation-summary-title">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Please correct the following errors:
                </div>
                <ul class="validation-summary-list">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Basic Information -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Shift Title <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="title"
                               name="title"
                               x-model="form.title"
                               @blur="validateField('title')"
                               @input="clearError('title')"
                               required
                               minlength="5"
                               maxlength="100"
                               placeholder="e.g., Warehouse Worker, Server, Retail Associate"
                               value="{{ old('title') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.title, 'form-field-valid': touched.title && !errors.title && form.title }"
                               :aria-invalid="errors.title ? 'true' : 'false'"
                               :aria-describedby="errors.title ? 'title-error' : null">
                        <div x-show="errors.title" class="field-error-message" id="title-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.title"></span>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <textarea id="description"
                                  name="description"
                                  rows="4"
                                  x-model="form.description"
                                  @blur="validateField('description')"
                                  @input="clearError('description')"
                                  required
                                  minlength="20"
                                  maxlength="2000"
                                  placeholder="Describe the shift responsibilities, requirements, and any important details..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                  :class="{ 'form-field-error': errors.description, 'form-field-valid': touched.description && !errors.description && form.description }"
                                  :aria-invalid="errors.description ? 'true' : 'false'"
                                  :aria-describedby="errors.description ? 'description-error' : 'description-hint'">{{ old('description') }}</textarea>
                        <div class="flex justify-between mt-1">
                            <div x-show="errors.description" class="field-error-message" id="description-error" role="alert">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="errors.description"></span>
                            </div>
                            <span x-show="!errors.description" class="text-xs text-gray-500" id="description-hint" x-text="form.description.length + '/2000 characters'"></span>
                        </div>
                    </div>

                    <div>
                        <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">
                            Industry <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="industry"
                                name="industry"
                                x-model="form.industry"
                                @change="validateField('industry')"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                :class="{ 'form-field-error': errors.industry, 'form-field-valid': touched.industry && !errors.industry && form.industry }"
                                :aria-invalid="errors.industry ? 'true' : 'false'"
                                :aria-describedby="errors.industry ? 'industry-error' : null">
                            <option value="">Select an industry</option>
                            <option value="hospitality" {{ old('industry') == 'hospitality' ? 'selected' : '' }}>Hospitality</option>
                            <option value="retail" {{ old('industry') == 'retail' ? 'selected' : '' }}>Retail</option>
                            <option value="warehouse" {{ old('industry') == 'warehouse' ? 'selected' : '' }}>Warehouse & Logistics</option>
                            <option value="events" {{ old('industry') == 'events' ? 'selected' : '' }}>Events & Catering</option>
                            <option value="healthcare" {{ old('industry') == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                            <option value="construction" {{ old('industry') == 'construction' ? 'selected' : '' }}>Construction</option>
                            <option value="manufacturing" {{ old('industry') == 'manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                            <option value="other" {{ old('industry') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <div x-show="errors.industry" class="field-error-message" id="industry-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.industry"></span>
                        </div>
                    </div>

                    <div>
                        <label for="job_category" class="block text-sm font-medium text-gray-700 mb-2">
                            Job Category <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="job_category"
                                name="job_category"
                                x-model="form.job_category"
                                @change="validateField('job_category')"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                :class="{ 'form-field-error': errors.job_category, 'form-field-valid': touched.job_category && !errors.job_category && form.job_category }"
                                :aria-invalid="errors.job_category ? 'true' : 'false'"
                                :aria-describedby="errors.job_category ? 'job_category-error' : null">
                            <option value="">Select a category</option>
                            <option value="labor">General Labor</option>
                            <option value="service">Customer Service</option>
                            <option value="delivery">Delivery & Transportation</option>
                            <option value="admin">Administrative</option>
                            <option value="technical">Technical</option>
                            <option value="supervisor">Supervisor/Management</option>
                        </select>
                        <div x-show="errors.job_category" class="field-error-message" id="job_category-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.job_category"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Schedule</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="shift_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Shift Date <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="date"
                               id="shift_date"
                               name="shift_date"
                               x-model="form.shift_date"
                               @change="validateField('shift_date')"
                               required
                               value="{{ old('shift_date') }}"
                               min="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.shift_date, 'form-field-valid': touched.shift_date && !errors.shift_date && form.shift_date }"
                               :aria-invalid="errors.shift_date ? 'true' : 'false'"
                               :aria-describedby="errors.shift_date ? 'shift_date-error' : null">
                        <div x-show="errors.shift_date" class="field-error-message" id="shift_date-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.shift_date"></span>
                        </div>
                    </div>

                    <div>
                        <label for="workers_needed" class="block text-sm font-medium text-gray-700 mb-2">
                            Workers Needed <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="number"
                               id="workers_needed"
                               name="workers_needed"
                               x-model="form.workers_needed"
                               @blur="validateField('workers_needed')"
                               @input="clearError('workers_needed')"
                               min="1"
                               max="50"
                               value="{{ old('workers_needed', 1) }}"
                               required
                               inputmode="numeric"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.workers_needed, 'form-field-valid': touched.workers_needed && !errors.workers_needed && form.workers_needed }"
                               :aria-invalid="errors.workers_needed ? 'true' : 'false'"
                               :aria-describedby="errors.workers_needed ? 'workers_needed-error' : null">
                        <div x-show="errors.workers_needed" class="field-error-message" id="workers_needed-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.workers_needed"></span>
                        </div>
                    </div>

                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                            Start Time <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="time"
                               id="start_time"
                               name="start_time"
                               x-model="form.start_time"
                               @change="validateField('start_time'); validateTimeRange()"
                               required
                               value="{{ old('start_time') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.start_time, 'form-field-valid': touched.start_time && !errors.start_time && form.start_time }"
                               :aria-invalid="errors.start_time ? 'true' : 'false'"
                               :aria-describedby="errors.start_time ? 'start_time-error' : null">
                        <div x-show="errors.start_time" class="field-error-message" id="start_time-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.start_time"></span>
                        </div>
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">
                            End Time <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="time"
                               id="end_time"
                               name="end_time"
                               x-model="form.end_time"
                               @change="validateField('end_time'); validateTimeRange()"
                               required
                               value="{{ old('end_time') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.end_time || errors.time_range, 'form-field-valid': touched.end_time && !errors.end_time && !errors.time_range && form.end_time }"
                               :aria-invalid="(errors.end_time || errors.time_range) ? 'true' : 'false'"
                               :aria-describedby="(errors.end_time || errors.time_range) ? 'end_time-error' : null">
                        <div x-show="errors.end_time || errors.time_range" class="field-error-message" id="end_time-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.end_time || errors.time_range"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Location</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($venues->count() > 0)
                    <div class="md:col-span-2">
                        <label for="venue_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Select Venue (Optional)
                        </label>
                        <select id="venue_id"
                                name="venue_id"
                                x-model="form.venue_id"
                                @change="handleVenueSelection()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">-- Select a venue or enter address manually --</option>
                            @foreach($venues as $venue)
                            <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>
                                {{ $venue->name }} - {{ $venue->address }}, {{ $venue->city }}
                            </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Selecting a venue will auto-fill the address fields below</p>
                    </div>
                    @endif

                    <div class="md:col-span-2">
                        <label for="location_address" class="block text-sm font-medium text-gray-700 mb-2">
                            Street Address <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="location_address"
                               name="location_address"
                               x-model="form.location_address"
                               @blur="validateField('location_address')"
                               @input="clearError('location_address')"
                               required
                               minlength="5"
                               maxlength="255"
                               placeholder="123 Main Street"
                               value="{{ old('location_address') }}"
                               autocomplete="street-address"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.location_address, 'form-field-valid': touched.location_address && !errors.location_address && form.location_address }"
                               :aria-invalid="errors.location_address ? 'true' : 'false'"
                               :aria-describedby="errors.location_address ? 'location_address-error' : null">
                        <div x-show="errors.location_address" class="field-error-message" id="location_address-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.location_address"></span>
                        </div>
                    </div>

                    <div>
                        <label for="location_city" class="block text-sm font-medium text-gray-700 mb-2">
                            City <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="location_city"
                               name="location_city"
                               x-model="form.location_city"
                               @blur="validateField('location_city')"
                               @input="clearError('location_city')"
                               required
                               minlength="2"
                               maxlength="100"
                               value="{{ old('location_city') }}"
                               autocomplete="address-level2"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.location_city, 'form-field-valid': touched.location_city && !errors.location_city && form.location_city }"
                               :aria-invalid="errors.location_city ? 'true' : 'false'"
                               :aria-describedby="errors.location_city ? 'location_city-error' : null">
                        <div x-show="errors.location_city" class="field-error-message" id="location_city-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.location_city"></span>
                        </div>
                    </div>

                    <div>
                        <label for="location_state" class="block text-sm font-medium text-gray-700 mb-2">
                            State <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <select id="location_state"
                                name="location_state"
                                x-model="form.location_state"
                                @change="validateField('location_state')"
                                required
                                autocomplete="address-level1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                :class="{ 'form-field-error': errors.location_state, 'form-field-valid': touched.location_state && !errors.location_state && form.location_state }"
                                :aria-invalid="errors.location_state ? 'true' : 'false'"
                                :aria-describedby="errors.location_state ? 'location_state-error' : null">
                            <option value="">Select a state</option>
                            <option value="MA">Massachusetts</option>
                            <option value="NY">New York</option>
                            <option value="CA">California</option>
                            <option value="TX">Texas</option>
                            <!-- Add more states as needed -->
                        </select>
                        <div x-show="errors.location_state" class="field-error-message" id="location_state-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.location_state"></span>
                        </div>
                    </div>

                    <div>
                        <label for="location_zip" class="block text-sm font-medium text-gray-700 mb-2">
                            ZIP Code <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="text"
                               id="location_zip"
                               name="location_zip"
                               x-model="form.location_zip"
                               @blur="validateField('location_zip')"
                               @input="clearError('location_zip')"
                               required
                               pattern="[0-9]{5}(-[0-9]{4})?"
                               minlength="5"
                               maxlength="10"
                               placeholder="02110"
                               value="{{ old('location_zip') }}"
                               inputmode="numeric"
                               autocomplete="postal-code"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.location_zip, 'form-field-valid': touched.location_zip && !errors.location_zip && form.location_zip }"
                               :aria-invalid="errors.location_zip ? 'true' : 'false'"
                               :aria-describedby="errors.location_zip ? 'location_zip-error' : null">
                        <div x-show="errors.location_zip" class="field-error-message" id="location_zip-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.location_zip"></span>
                        </div>
                    </div>

                    <div>
                        <label for="parking_info" class="block text-sm font-medium text-gray-700 mb-2">Parking Information (Optional)</label>
                        <input type="text"
                               id="parking_info"
                               name="parking_info"
                               x-model="form.parking_info"
                               maxlength="255"
                               placeholder="e.g., Free parking in rear lot"
                               value="{{ old('parking_info') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Compensation -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Compensation</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="base_rate" class="block text-sm font-medium text-gray-700 mb-2">
                            Base Hourly Rate ($) <span class="text-red-500" aria-hidden="true">*</span>
                        </label>
                        <input type="number"
                               id="base_rate"
                               name="base_rate"
                               x-model="form.base_rate"
                               @blur="validateField('base_rate')"
                               @input="clearError('base_rate')"
                               min="15"
                               max="100"
                               step="0.50"
                               required
                               inputmode="decimal"
                               placeholder="18.00"
                               value="{{ old('base_rate', 15.00) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                               :class="{ 'form-field-error': errors.base_rate, 'form-field-valid': touched.base_rate && !errors.base_rate && form.base_rate }"
                               :aria-invalid="errors.base_rate ? 'true' : 'false'"
                               :aria-describedby="errors.base_rate ? 'base_rate-error' : 'base_rate-hint'">
                        <p class="text-xs text-gray-500 mt-1" id="base_rate-hint" x-show="!errors.base_rate">Minimum $15.00/hour</p>
                        <div x-show="errors.base_rate" class="field-error-message" id="base_rate-error" role="alert">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="errors.base_rate"></span>
                        </div>
                    </div>

                    <div>
                        <label for="surge_multiplier" class="block text-sm font-medium text-gray-700 mb-2">Surge Multiplier (Optional)</label>
                        <select id="surge_multiplier"
                                name="surge_multiplier"
                                x-model="form.surge_multiplier"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="1.0">None (1.0x)</option>
                            <option value="1.25">+25% (1.25x)</option>
                            <option value="1.5">+50% (1.5x)</option>
                            <option value="1.75">+75% (1.75x)</option>
                            <option value="2.0">+100% (2.0x)</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Increase rate to attract workers faster</p>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center">
                            <input type="checkbox"
                                   id="tips_expected"
                                   name="tips_expected"
                                   value="1"
                                   x-model="form.tips_expected"
                                   class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                            <label for="tips_expected" class="ml-2 text-sm text-gray-700">Tips expected</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requirements -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Requirements</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3" id="badges-label">Required Badges</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3" role="group" aria-labelledby="badges-label">
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="required_badges[]" value="food_handler" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Food Handler</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="required_badges[]" value="alcohol_server" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Alcohol Server</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="required_badges[]" value="forklift" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Forklift Certified</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="required_badges[]" value="cpr" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">CPR/First Aid</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="required_badges[]" value="drivers_license" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Driver's License</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="dress_code" class="block text-sm font-medium text-gray-700 mb-2">Dress Code</label>
                        <input type="text"
                               id="dress_code"
                               name="dress_code"
                               x-model="form.dress_code"
                               maxlength="255"
                               placeholder="e.g., Black pants, white shirt, non-slip shoes"
                               value="{{ old('dress_code') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                        <textarea id="special_instructions"
                                  name="special_instructions"
                                  rows="3"
                                  x-model="form.special_instructions"
                                  maxlength="1000"
                                  placeholder="Any additional information workers should know..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">{{ old('special_instructions') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('business.shifts.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <div class="flex space-x-3">
                    <button type="submit"
                            name="status"
                            value="draft"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50"
                            :class="{ 'btn-loading': isSubmitting && submitAction === 'draft' }"
                            :disabled="isSubmitting"
                            @click="submitAction = 'draft'">
                        Save as Draft
                    </button>
                    <button type="submit"
                            name="status"
                            value="open"
                            class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium"
                            :class="{ 'btn-loading': isSubmitting && submitAction === 'open' }"
                            :disabled="isSubmitting"
                            :aria-busy="isSubmitting"
                            @click="submitAction = 'open'">
                        Post Shift
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function shiftForm() {
    return {
        venues: @json($venues->map(function($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'address' => $venue->address,
                'city' => $venue->city,
                'state' => $venue->state,
                'postal_code' => $venue->postal_code,
            ];
        })),
        form: {
            title: '{{ old('title') }}',
            description: '{{ old('description') }}',
            industry: '{{ old('industry') }}',
            job_category: '{{ old('job_category') }}',
            shift_date: '{{ old('shift_date') }}',
            workers_needed: '{{ old('workers_needed', 1) }}',
            start_time: '{{ old('start_time') }}',
            end_time: '{{ old('end_time') }}',
            venue_id: '{{ old('venue_id') }}',
            location_address: '{{ old('location_address') }}',
            location_city: '{{ old('location_city') }}',
            location_state: '{{ old('location_state') }}',
            location_zip: '{{ old('location_zip') }}',
            parking_info: '{{ old('parking_info') }}',
            base_rate: '{{ old('base_rate', 15.00) }}',
            surge_multiplier: '{{ old('surge_multiplier', '1.0') }}',
            tips_expected: {{ old('tips_expected') ? 'true' : 'false' }},
            dress_code: '{{ old('dress_code') }}',
            special_instructions: '{{ old('special_instructions') }}'
        },
        errors: {},
        touched: {},
        isSubmitting: false,
        submitAction: 'open',
        showValidationSummary: false,

        validateField(field) {
            this.touched[field] = true;
            delete this.errors[field];

            const value = this.form[field];

            switch(field) {
                case 'title':
                    if (!value || value.trim() === '') {
                        this.errors[field] = 'Shift title is required';
                    } else if (value.length < 5) {
                        this.errors[field] = 'Title must be at least 5 characters';
                    } else if (value.length > 100) {
                        this.errors[field] = 'Title must be less than 100 characters';
                    }
                    break;

                case 'description':
                    if (!value || value.trim() === '') {
                        this.errors[field] = 'Description is required';
                    } else if (value.length < 20) {
                        this.errors[field] = 'Description must be at least 20 characters';
                    } else if (value.length > 2000) {
                        this.errors[field] = 'Description must be less than 2000 characters';
                    }
                    break;

                case 'industry':
                    if (!value) {
                        this.errors[field] = 'Please select an industry';
                    }
                    break;

                case 'job_category':
                    if (!value) {
                        this.errors[field] = 'Please select a job category';
                    }
                    break;

                case 'shift_date':
                    if (!value) {
                        this.errors[field] = 'Shift date is required';
                    } else {
                        const selectedDate = new Date(value);
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        if (selectedDate < today) {
                            this.errors[field] = 'Shift date must be today or in the future';
                        }
                    }
                    break;

                case 'workers_needed':
                    if (!value || value < 1) {
                        this.errors[field] = 'At least 1 worker is required';
                    } else if (value > 50) {
                        this.errors[field] = 'Maximum 50 workers per shift';
                    } else if (!Number.isInteger(Number(value))) {
                        this.errors[field] = 'Please enter a whole number';
                    }
                    break;

                case 'start_time':
                    if (!value) {
                        this.errors[field] = 'Start time is required';
                    }
                    break;

                case 'end_time':
                    if (!value) {
                        this.errors[field] = 'End time is required';
                    }
                    break;

                case 'location_address':
                    if (!value || value.trim() === '') {
                        this.errors[field] = 'Street address is required';
                    } else if (value.length < 5) {
                        this.errors[field] = 'Please enter a complete street address';
                    }
                    break;

                case 'location_city':
                    if (!value || value.trim() === '') {
                        this.errors[field] = 'City is required';
                    } else if (value.length < 2) {
                        this.errors[field] = 'Please enter a valid city name';
                    }
                    break;

                case 'location_state':
                    if (!value) {
                        this.errors[field] = 'Please select a state';
                    }
                    break;

                case 'location_zip':
                    if (!value || value.trim() === '') {
                        this.errors[field] = 'ZIP code is required';
                    } else if (!/^[0-9]{5}(-[0-9]{4})?$/.test(value)) {
                        this.errors[field] = 'Please enter a valid ZIP code (e.g., 02110 or 02110-1234)';
                    }
                    break;

                case 'base_rate':
                    if (!value || value === '') {
                        this.errors[field] = 'Hourly rate is required';
                    } else if (parseFloat(value) < 15) {
                        this.errors[field] = 'Minimum hourly rate is $15.00';
                    } else if (parseFloat(value) > 100) {
                        this.errors[field] = 'Maximum hourly rate is $100.00';
                    }
                    break;
            }

            return !this.errors[field];
        },

        validateTimeRange() {
            delete this.errors.time_range;

            if (this.form.start_time && this.form.end_time) {
                const start = this.form.start_time;
                const end = this.form.end_time;

                // Simple time comparison (works for same-day shifts)
                // For overnight shifts, end time can be less than start time
                if (start === end) {
                    this.errors.time_range = 'End time must be different from start time';
                }
            }
        },

        clearError(field) {
            if (this.touched[field]) {
                this.validateField(field);
            }
        },

        validateAll() {
            const requiredFields = [
                'title', 'description', 'industry', 'job_category',
                'shift_date', 'workers_needed', 'start_time', 'end_time',
                'location_address', 'location_city', 'location_state', 'location_zip',
                'base_rate'
            ];

            let isValid = true;

            requiredFields.forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            this.validateTimeRange();
            if (this.errors.time_range) {
                isValid = false;
            }

            return isValid;
        },

        handleSubmit(event) {
            this.showValidationSummary = true;

            if (!this.validateAll()) {
                event.preventDefault();

                // Announce errors to screen readers
                this.announceErrors();

                // Focus first field with error
                const errorFields = Object.keys(this.errors);
                if (errorFields.length > 0) {
                    const firstErrorField = errorFields[0];
                    const element = document.getElementById(firstErrorField);
                    if (element) {
                        element.focus();
                        element.classList.add('shake');
                        setTimeout(() => {
                            element.classList.remove('shake');
                        }, 400);
                    }
                }

                // Scroll to validation summary
                window.scrollTo({ top: 0, behavior: 'smooth' });

                return false;
            }

            this.isSubmitting = true;
        },

        announceErrors() {
            const announcer = document.getElementById('validation-announcer');
            if (announcer) {
                const errorCount = Object.keys(this.errors).length;
                announcer.textContent = `Form has ${errorCount} error${errorCount > 1 ? 's' : ''}. Please correct the highlighted fields.`;
                setTimeout(() => {
                    announcer.textContent = '';
                }, 3000);
            }
        },

        handleVenueSelection() {
            const venueId = this.form.venue_id;
            if (!venueId) {
                return;
            }

            const venue = this.venues.find(v => v.id == venueId);
            if (venue) {
                this.form.location_address = venue.address || '';
                this.form.location_city = venue.city || '';
                this.form.location_state = venue.state || '';
                this.form.location_zip = venue.postal_code || '';
                
                // Trigger validation for filled fields
                if (venue.address) this.validateField('location_address');
                if (venue.city) this.validateField('location_city');
                if (venue.state) this.validateField('location_state');
                if (venue.postal_code) this.validateField('location_zip');
            }
        }
    };
}
</script>
@endpush
