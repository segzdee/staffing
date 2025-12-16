@extends('layouts.authenticated')

@section('title', 'Create Shift Template')
@section('page-title', 'Create Shift Template')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('business.templates.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
    </svg>
    <span>Templates</span>
</a>
@endsection

@section('content')
<div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('business.templates.index') }}" class="text-brand-600 hover:text-brand-700 text-sm font-medium mb-2 inline-block">
                &larr; Back to Templates
            </a>
            <h2 class="text-2xl font-bold text-gray-900">Create Shift Template</h2>
            <p class="text-sm text-gray-500 mt-1">Set up a reusable template for recurring shifts</p>
        </div>
    </div>

    <form action="{{ route('business.templates.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           placeholder="e.g., Morning Warehouse Shift">
                </div>

                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                              placeholder="Brief description of this shift template..."></textarea>
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Shift Title *</label>
                    <input type="text" id="title" name="title" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           placeholder="e.g., Warehouse Associate">
                </div>

                <div>
                    <label for="industry" class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
                    <select id="industry" name="industry"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <option value="">Select Industry</option>
                        <option value="retail">Retail</option>
                        <option value="hospitality">Hospitality</option>
                        <option value="warehouse">Warehouse & Logistics</option>
                        <option value="healthcare">Healthcare</option>
                        <option value="manufacturing">Manufacturing</option>
                        <option value="events">Events</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Schedule -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>

                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                    <input type="time" id="end_time" name="end_time" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>

                <div>
                    <label for="break_duration" class="block text-sm font-medium text-gray-700 mb-2">Break Duration (minutes)</label>
                    <input type="number" id="break_duration" name="break_duration" min="0" value="30"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>

                <div>
                    <label for="workers_needed" class="block text-sm font-medium text-gray-700 mb-2">Workers Needed *</label>
                    <input type="number" id="workers_needed" name="workers_needed" min="1" required value="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>
            </div>
        </div>

        <!-- Compensation -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Compensation</h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">Hourly Rate ($) *</label>
                    <input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.01" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           placeholder="15.00">
                </div>

                <div>
                    <label for="overtime_rate" class="block text-sm font-medium text-gray-700 mb-2">Overtime Rate ($)</label>
                    <input type="number" id="overtime_rate" name="overtime_rate" min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           placeholder="22.50">
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="tips_included" class="h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Tips may be included</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Requirements -->
        <div class="bg-white border border-gray-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Requirements</h3>
            <div class="space-y-4">
                <div>
                    <label for="requirements" class="block text-sm font-medium text-gray-700 mb-2">Shift Requirements</label>
                    <textarea id="requirements" name="requirements" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                              placeholder="List any requirements or qualifications (one per line)..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Enter each requirement on a new line</p>
                </div>

                <div>
                    <label for="skills" class="block text-sm font-medium text-gray-700 mb-2">Required Skills</label>
                    <input type="text" id="skills" name="skills"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                           placeholder="e.g., forklift, customer service, food handling">
                    <p class="text-xs text-gray-500 mt-1">Comma-separated list of skills</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="certification_required" class="h-4 w-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                        <span class="ml-2 text-sm text-gray-700">Certification required</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('business.templates.index') }}" class="px-6 py-2 text-gray-600 hover:text-gray-900">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                Create Template
            </button>
        </div>
    </form>
</div>
@endsection
