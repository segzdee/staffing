@extends('layouts.authenticated')

@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('sidebar-nav')
<a href="{{ route('dashboard.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('worker.profile') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
</a>
<a href="{{ route('worker.profile.badges') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
    </svg>
    <span>Badges</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Profile Completeness Card -->
        <div class="bg-gradient-to-r from-brand-500 to-brand-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold">Profile Completeness</h2>
                <span class="text-3xl font-bold">{{ $profileCompleteness ?? 60 }}%</span>
            </div>
            <div class="w-full bg-brand-700 rounded-full h-3">
                <div class="bg-white h-3 rounded-full" style="width: {{ $profileCompleteness ?? 60 }}%"></div>
            </div>
            <p class="text-sm text-brand-100 mt-3">Complete your profile to increase your chances of getting hired!</p>
        </div>

        <!-- Profile Form -->
        <div class="bg-white rounded-xl border border-gray-200 p-8 space-y-8">
            <form action="{{ route('worker.profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Personal Information</h2>
                    <div class="space-y-6">
                        <!-- Profile Photo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Profile Photo</label>
                            <div class="flex items-center space-x-4">
                                <div class="w-20 h-20 rounded-full bg-gray-200 overflow-hidden">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar }}" alt="Profile" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-500">
                                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden">
                                    <label for="avatar" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer">
                                        Change Photo
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1">JPG, PNG. Max 2MB.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Bio -->
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio / About Me</label>
                            <textarea id="bio" name="bio" rows="4"
                                      placeholder="Tell businesses about yourself, your experience, and what makes you a great worker..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">{{ ($profile ?? null)?->bio ?? '' }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Experience & Skills -->
                <div class="pt-6 border-t border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Experience & Skills</h2>
                    <div class="space-y-6">
                        <!-- Industries -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Preferred Industries</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="industries[]" value="hospitality" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Hospitality</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="industries[]" value="retail" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Retail</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="industries[]" value="warehouse" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Warehouse</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="industries[]" value="events" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Events</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="industries[]" value="healthcare" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Healthcare</span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="checkbox" name="industries[]" value="construction" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Construction</span>
                                </label>
                            </div>
                        </div>

                        <!-- Experience Level -->
                        <div>
                            <label for="experience_level" class="block text-sm font-medium text-gray-700 mb-2">Experience Level</label>
                            <select id="experience_level" name="experience_level"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                                <option value="">Select experience level</option>
                                <option value="entry" {{ (($profile ?? null)?->experience_level ?? '') === 'entry' ? 'selected' : '' }}>Entry Level (0-1 years)</option>
                                <option value="intermediate" {{ (($profile ?? null)?->experience_level ?? '') === 'intermediate' ? 'selected' : '' }}>Intermediate (2-5 years)</option>
                                <option value="experienced" {{ (($profile ?? null)?->experience_level ?? '') === 'experienced' ? 'selected' : '' }}>Experienced (5+ years)</option>
                            </select>
                        </div>

                        <!-- Skills -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Key Skills</label>
                            <input type="text" name="skills"
                                   placeholder="e.g., Customer Service, POS Systems, Forklift Operation"
                                   value="{{ ($profile ?? null)?->skills ?? '' }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Separate skills with commas</p>
                        </div>
                    </div>
                </div>

                <!-- Availability Preferences -->
                <div class="pt-6 border-t border-gray-200">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">Availability Preferences</h2>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="max_distance" class="block text-sm font-medium text-gray-700 mb-2">Maximum Travel Distance (miles)</label>
                                <input type="number" id="max_distance" name="max_distance" min="1" max="100" value="{{ ($profile ?? null)?->max_distance ?? 25 }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="min_hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">Minimum Hourly Rate ($)</label>
                                <input type="number" id="min_hourly_rate" name="min_hourly_rate" min="15" max="100" step="0.50" value="{{ ($profile ?? null)?->min_hourly_rate ?? 15 }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            </div>
                        </div>

                        <!-- Preferred Shift Times -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Preferred Shift Times</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="shift_times[]" value="morning" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Morning (6am - 12pm)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="shift_times[]" value="afternoon" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Afternoon (12pm - 6pm)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="shift_times[]" value="evening" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Evening (6pm - 12am)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="shift_times[]" value="overnight" class="w-4 h-4 text-brand-600 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Overnight (12am - 6am)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Transportation -->
                        <div>
                            <label for="has_transportation" class="block text-sm font-medium text-gray-700 mb-2">Transportation</label>
                            <select id="has_transportation" name="has_transportation"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                                <option value="1">I have reliable transportation</option>
                                <option value="0">I rely on public transit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                    <a href="{{ route('dashboard.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                        Save Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('worker.profile.badges') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:border-brand-300 transition-colors">
                <div class="flex items-center">
                    <div class="p-3 bg-brand-100 rounded-lg">
                        <svg class="w-6 h-6 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-gray-900">Manage Badges</h3>
                        <p class="text-sm text-gray-600">View and request certifications</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('worker.calendar') }}" class="bg-white rounded-xl border border-gray-200 p-6 hover:border-brand-300 transition-colors">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-gray-900">Set Availability</h3>
                        <p class="text-sm text-gray-600">Manage your calendar</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
