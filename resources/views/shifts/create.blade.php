@extends('layouts.authenticated')

@section('title', 'Post a Shift')
@section('page-title', 'Post a New Shift')

@section('sidebar-nav')
<a href="{{ route('business.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
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

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto">
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

        <form action="{{ route('shifts.store') }}" method="POST" class="bg-white rounded-xl border border-gray-200 p-8 space-y-8">
            @csrf

            <!-- Basic Information -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Basic Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Shift Title *</label>
                        <input type="text" id="title" name="title" required
                               placeholder="e.g., Warehouse Worker, Server, Retail Associate"
                               value="{{ old('title') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea id="description" name="description" rows="4" required
                                  placeholder="Describe the shift responsibilities, requirements, and any important details..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">{{ old('description') }}</textarea>
                    </div>

                    <div>
                        <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">Industry *</label>
                        <select id="industry" name="industry" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
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
                    </div>

                    <div>
                        <label for="job_category" class="block text-sm font-medium text-gray-700 mb-2">Job Category *</label>
                        <select id="job_category" name="job_category" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">Select a category</option>
                            <option value="labor">General Labor</option>
                            <option value="service">Customer Service</option>
                            <option value="delivery">Delivery & Transportation</option>
                            <option value="admin">Administrative</option>
                            <option value="technical">Technical</option>
                            <option value="supervisor">Supervisor/Management</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Schedule -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Schedule</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="shift_date" class="block text-sm font-medium text-gray-700 mb-2">Shift Date *</label>
                        <input type="date" id="shift_date" name="shift_date" required
                               value="{{ old('shift_date') }}" min="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="workers_needed" class="block text-sm font-medium text-gray-700 mb-2">Workers Needed *</label>
                        <input type="number" id="workers_needed" name="workers_needed" min="1" max="50" value="{{ old('workers_needed', 1) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required
                               value="{{ old('start_time') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                        <input type="time" id="end_time" name="end_time" required
                               value="{{ old('end_time') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-6">Location</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="location_address" class="block text-sm font-medium text-gray-700 mb-2">Street Address *</label>
                        <input type="text" id="location_address" name="location_address" required
                               placeholder="123 Main Street"
                               value="{{ old('location_address') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="location_city" class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                        <input type="text" id="location_city" name="location_city" required
                               value="{{ old('location_city') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="location_state" class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                        <select id="location_state" name="location_state" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">Select a state</option>
                            <option value="MA">Massachusetts</option>
                            <option value="NY">New York</option>
                            <option value="CA">California</option>
                            <option value="TX">Texas</option>
                            <!-- Add more states as needed -->
                        </select>
                    </div>

                    <div>
                        <label for="location_zip" class="block text-sm font-medium text-gray-700 mb-2">ZIP Code *</label>
                        <input type="text" id="location_zip" name="location_zip" required
                               placeholder="02110"
                               value="{{ old('location_zip') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="parking_info" class="block text-sm font-medium text-gray-700 mb-2">Parking Information (Optional)</label>
                        <input type="text" id="parking_info" name="parking_info"
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
                        <label for="base_rate" class="block text-sm font-medium text-gray-700 mb-2">Base Hourly Rate ($) *</label>
                        <input type="number" id="base_rate" name="base_rate" min="15" max="100" step="0.50" required
                               placeholder="18.00"
                               value="{{ old('base_rate', 15.00) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Minimum $15.00/hour</p>
                    </div>

                    <div>
                        <label for="surge_multiplier" class="block text-sm font-medium text-gray-700 mb-2">Surge Multiplier (Optional)</label>
                        <select id="surge_multiplier" name="surge_multiplier"
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
                            <input type="checkbox" id="tips_expected" name="tips_expected" value="1"
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
                        <label class="block text-sm font-medium text-gray-700 mb-3">Required Badges</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
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
                        <input type="text" id="dress_code" name="dress_code"
                               placeholder="e.g., Black pants, white shirt, non-slip shoes"
                               value="{{ old('dress_code') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" rows="3"
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
                    <button type="submit" name="status" value="draft" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Save as Draft
                    </button>
                    <button type="submit" name="status" value="open" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        Post Shift
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
