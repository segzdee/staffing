@extends('layouts.authenticated')

@section('title', 'Edit Agency Profile')
@section('page-title', 'Edit Agency Profile')

@section('sidebar-nav')
<a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    <span>Dashboard</span>
</a>
<a href="{{ route('agency.profile') }}" class="sidebar-link active flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
    </svg>
    <span>Profile</span>
</a>
<a href="{{ route('agency.workers.index') }}" class="flex items-center space-x-3 px-3 py-2 text-muted-foreground hover:text-foreground hover:bg-accent rounded-lg transition-colors">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
    <span>Workers</span>
</a>
@endsection

@section('content')
<div class="p-6">
    <div class="max-w-3xl mx-auto">
        <div class="bg-card rounded-xl border border-border overflow-hidden">
            <div class="p-6 border-b border-border">
                <h2 class="text-xl font-bold text-foreground">Edit Agency Profile</h2>
                <p class="text-sm text-muted-foreground mt-1">Update your agency information to attract clients and workers.</p>
            </div>

            <form action="{{ route('agency.profile.update') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- Agency Logo -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-3">Agency Logo</label>
                    <div class="flex items-center space-x-4">
                        <div class="w-20 h-20 rounded-full bg-muted overflow-hidden">
                            @if($user->avatar)
                                <img src="{{ asset($user->avatar) }}" alt="Agency Logo" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-muted-foreground">
                                    {{ strtoupper(substr($profile->agency_name ?? $user->name, 0, 2)) }}
                                </div>
                            @endif
                        </div>
                        <div>
                            <input type="file" name="avatar" id="avatar" accept="image/*" class="hidden">
                            <label for="avatar" class="btn-secondary cursor-pointer">
                                Change Logo
                            </label>
                            <p class="text-xs text-muted-foreground mt-1">JPG, PNG. Max 2MB.</p>
                        </div>
                    </div>
                </div>

                <!-- Agency Name -->
                <div>
                    <label for="agency_name" class="block text-sm font-medium text-foreground mb-2">Agency Name *</label>
                    <input type="text" id="agency_name" name="agency_name"
                           value="{{ old('agency_name', $profile->agency_name ?? $user->name) }}"
                           class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring"
                           required>
                    @error('agency_name')
                        <p class="text-sm text-destructive mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Contact Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-foreground mb-2">Contact Name</label>
                        <input type="text" id="contact_name" name="contact_name"
                               value="{{ old('contact_name', $profile->contact_name ?? '') }}"
                               class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-foreground mb-2">Phone</label>
                        <input type="tel" id="phone" name="phone"
                               value="{{ old('phone', $profile->phone ?? '') }}"
                               class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-foreground mb-2">Business Email</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $profile->email ?? $user->email) }}"
                           class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <!-- Address -->
                <div>
                    <label for="address" class="block text-sm font-medium text-foreground mb-2">Street Address</label>
                    <input type="text" id="address" name="address"
                           value="{{ old('address', $profile->address ?? '') }}"
                           class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-medium text-foreground mb-2">City</label>
                        <input type="text" id="city" name="city"
                               value="{{ old('city', $profile->city ?? '') }}"
                               class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                    <div>
                        <label for="state" class="block text-sm font-medium text-foreground mb-2">State/Province</label>
                        <input type="text" id="state" name="state"
                               value="{{ old('state', $profile->state ?? '') }}"
                               class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                    <div>
                        <label for="zip_code" class="block text-sm font-medium text-foreground mb-2">ZIP/Postal Code</label>
                        <input type="text" id="zip_code" name="zip_code"
                               value="{{ old('zip_code', $profile->zip_code ?? '') }}"
                               class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-foreground mb-2">Country</label>
                        <input type="text" id="country" name="country"
                               value="{{ old('country', $profile->country ?? '') }}"
                               class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    </div>
                </div>

                <!-- Commission Rate -->
                <div>
                    <label for="commission_rate" class="block text-sm font-medium text-foreground mb-2">Default Commission Rate (%)</label>
                    <input type="number" id="commission_rate" name="commission_rate"
                           value="{{ old('commission_rate', $profile->commission_rate ?? 10) }}"
                           min="0" max="50" step="0.5"
                           class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">
                    <p class="text-xs text-muted-foreground mt-1">Commission percentage charged on worker placements.</p>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-foreground mb-2">Agency Description</label>
                    <textarea id="description" name="description" rows="4"
                              placeholder="Tell clients and workers about your agency, your specializations, and what makes you stand out..."
                              class="w-full px-4 py-2 bg-background border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring">{{ old('description', $profile->description ?? '') }}</textarea>
                </div>

                <!-- Specializations -->
                <div>
                    <label class="block text-sm font-medium text-foreground mb-3">Specializations</label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @php
                            $specializations = ['hospitality', 'healthcare', 'retail', 'warehouse', 'events', 'construction', 'manufacturing', 'office', 'security'];
                            $currentSpecializations = json_decode($profile->specializations ?? '[]', true) ?? [];
                        @endphp
                        @foreach($specializations as $spec)
                            <label class="flex items-center p-3 border border-input rounded-lg cursor-pointer hover:bg-accent transition-colors">
                                <input type="checkbox" name="specializations[]" value="{{ $spec }}"
                                       {{ in_array($spec, $currentSpecializations) ? 'checked' : '' }}
                                       class="w-4 h-4 text-primary border-input rounded">
                                <span class="ml-2 text-sm text-foreground">{{ ucfirst($spec) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-6 border-t border-border">
                    <a href="{{ route('agency.profile') }}" class="text-sm text-muted-foreground hover:text-foreground">Cancel</a>
                    <button type="submit" class="btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
