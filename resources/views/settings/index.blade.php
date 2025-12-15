@extends('layouts.authenticated')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('sidebar-nav')
@if(auth()->user()->user_type === 'worker')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@elseif(auth()->user()->user_type === 'business')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
@endif
<a href="{{ route('settings.index') }}" class="flex items-center space-x-3 px-3 py-2 text-gray-900 bg-brand-50 rounded-lg font-medium">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>Settings</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Tab Navigation -->
        <div class="bg-white rounded-xl border border-gray-200">
            <nav class="flex border-b border-gray-200">
                <button onclick="showTab('profile')" id="tab-profile" class="tab-btn px-6 py-3 border-b-2 border-brand-600 text-brand-600 font-medium text-sm">
                    Profile
                </button>
                <button onclick="showTab('password')" id="tab-password" class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300 font-medium text-sm">
                    Password
                </button>
                <button onclick="showTab('notifications')" id="tab-notifications" class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300 font-medium text-sm">
                    Notifications
                </button>
                <button onclick="showTab('account')" id="tab-account" class="tab-btn px-6 py-3 border-b-2 border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300 font-medium text-sm">
                    Account
                </button>
            </nav>

            <!-- Profile Tab -->
            <div id="content-profile" class="tab-content p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Profile Information</h2>
                <form action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

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
                                </div>
                            </div>
                        </div>

                        <!-- Basic Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" id="name" name="name" value="{{ auth()->user()->name }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" id="email" name="email" value="{{ auth()->user()->email }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="{{ auth()->user()->phone ?? '' }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="{{ auth()->user()->date_of_birth ?? '' }}"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            </div>
                        </div>

                        <!-- Bio (Worker only) -->
                        @if(auth()->user()->user_type === 'worker')
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                            <textarea id="bio" name="bio" rows="4"
                                      placeholder="Tell businesses about yourself..."
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">{{ auth()->user()->workerProfile->bio ?? '' }}</textarea>
                        </div>
                        @endif

                        <!-- Business Name (Business only) -->
                        @if(auth()->user()->user_type === 'business')
                        <div>
                            <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">Business Name *</label>
                            <input type="text" id="business_name" name="business_name" value="{{ auth()->user()->businessProfile->business_name ?? '' }}" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>
                        @endif

                        <!-- Submit -->
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Password Tab -->
            <div id="content-password" class="tab-content hidden p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Change Password</h2>
                <form action="{{ route('settings.password.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6 max-w-md">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                        </div>

                        <div>
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password *</label>
                            <input type="password" id="new_password_confirmation" name="new_password_confirmation" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                                Update Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Notifications Tab -->
            <div id="content-notifications" class="tab-content hidden p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Notification Preferences</h2>
                <form action="{{ route('settings.notifications.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <!-- Email Notifications -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Email Notifications</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="email_shift_assigned" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">Shift assignments</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="email_application_status" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">Application status updates</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="email_messages" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">New messages</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="email_shift_reminders" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">Shift reminders (24 hours before)</span>
                                </label>
                            </div>
                        </div>

                        <!-- Push Notifications -->
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Push Notifications</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="push_shift_assigned" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">Shift assignments</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="push_messages" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">New messages</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="push_shift_starting" value="1" checked
                                           class="w-4 h-4 text-brand-600 border-gray-300 rounded focus:ring-brand-500">
                                    <span class="ml-3 text-sm text-gray-700">Shift starting soon (1 hour before)</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                                Save Preferences
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Account Tab -->
            <div id="content-account" class="tab-content hidden p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Account Management</h2>

                <!-- Account Info -->
                <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Account Type:</span>
                            <span class="ml-2 font-medium text-gray-900">{{ ucfirst(auth()->user()->user_type) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Member Since:</span>
                            <span class="ml-2 font-medium text-gray-900">{{ auth()->user()->created_at->format('M Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Account Status:</span>
                            <span class="ml-2 font-medium text-green-600">Active</span>
                        </div>
                        <div>
                            <span class="text-gray-600">Email Verified:</span>
                            <span class="ml-2 font-medium {{ auth()->user()->email_verified_at ? 'text-green-600' : 'text-red-600' }}">
                                {{ auth()->user()->email_verified_at ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-red-600 mb-4">Danger Zone</h3>

                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <h4 class="font-medium text-gray-900 mb-2">Delete Account</h4>
                        <p class="text-sm text-gray-600 mb-4">
                            Once you delete your account, there is no going back. All your data will be permanently removed.
                        </p>
                        <form action="{{ route('settings.account.delete') }}" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm">
                                Delete My Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });

    // Reset all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-brand-600', 'text-brand-600');
        btn.classList.add('border-transparent', 'text-gray-600');
    });

    // Show selected tab content
    document.getElementById('content-' + tabName).classList.remove('hidden');

    // Highlight selected tab button
    const selectedBtn = document.getElementById('tab-' + tabName);
    selectedBtn.classList.remove('border-transparent', 'text-gray-600');
    selectedBtn.classList.add('border-brand-600', 'text-brand-600');
}
</script>
@endsection
