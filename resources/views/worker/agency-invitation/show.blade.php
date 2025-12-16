@extends('layouts.guest')

@section('title', 'Agency Invitation')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-brand-50 to-white flex items-center justify-center p-4">
    <div class="max-w-lg w-full">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}">
                <h1 class="text-2xl font-bold text-brand-600">OvertimeStaff</h1>
            </a>
        </div>

        <!-- Invitation Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-brand-600 to-brand-700 px-6 py-8 text-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">You're Invited!</h1>
                <p class="text-brand-100">Join {{ $agencyProfile->agency_name ?? $agency->name }}'s worker pool</p>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Agency Info -->
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl mb-6">
                    <div class="w-14 h-14 bg-brand-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        @if($agency->avatar)
                            <img src="{{ $agency->avatar }}" alt="{{ $agencyProfile->agency_name ?? $agency->name }}" class="w-14 h-14 rounded-xl object-cover">
                        @else
                            <span class="text-xl font-bold text-brand-600">{{ strtoupper(substr($agencyProfile->agency_name ?? $agency->name, 0, 2)) }}</span>
                        @endif
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $agencyProfile->agency_name ?? $agency->name }}</h3>
                        @if($agencyProfile->business_model)
                            <p class="text-sm text-gray-500 capitalize">{{ str_replace('_', ' ', $agencyProfile->business_model) }}</p>
                        @endif
                        @if($agencyProfile->license_verified)
                            <span class="inline-flex items-center text-xs text-green-600 mt-1">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Verified Agency
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Personal Message -->
                @if($invitation->personal_message)
                <div class="mb-6 p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-sm text-gray-600 mb-1">Personal message:</p>
                    <p class="text-gray-800 italic">"{{ $invitation->personal_message }}"</p>
                </div>
                @endif

                <!-- Agency Statistics -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ $agencyStats['worker_count'] }}</p>
                        <p class="text-xs text-gray-500">Workers</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($agencyStats['completed_shifts']) }}</p>
                        <p class="text-xs text-gray-500">Shifts</p>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        @if($agencyStats['avg_rating'])
                            <p class="text-2xl font-bold text-gray-900">{{ $agencyStats['avg_rating'] }}</p>
                            <p class="text-xs text-gray-500">Avg Rating</p>
                        @else
                            <p class="text-2xl font-bold text-gray-900">-</p>
                            <p class="text-xs text-gray-500">Rating</p>
                        @endif
                    </div>
                </div>

                <!-- Benefits -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Benefits of joining:</h4>
                    <ul class="space-y-2">
                        <li class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Access to curated shift opportunities
                        </li>
                        <li class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Reliable and fast payments
                        </li>
                        <li class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Dedicated agency support
                        </li>
                        <li class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Build your professional network
                        </li>
                    </ul>
                </div>

                <!-- Commission Rate -->
                @if($invitation->preset_commission_rate)
                <div class="mb-6 p-4 bg-amber-50 border border-amber-100 rounded-xl">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700">Agency Commission Rate</span>
                        <span class="font-semibold text-gray-900">{{ $invitation->preset_commission_rate }}%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">This is the percentage the agency takes from shift payments.</p>
                </div>
                @endif

                <!-- Expiry Warning -->
                <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>This invitation expires on {{ $invitation->expires_at->format('F j, Y') }}</span>
                </div>

                <!-- Actions -->
                @if($currentUser)
                    @if($isInAgency)
                        <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center">
                            <svg class="w-8 h-8 text-green-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-green-800 font-medium">You're already a member of this agency!</p>
                            <a href="{{ route('worker.dashboard') }}" class="text-green-600 hover:text-green-700 text-sm mt-2 inline-block">
                                Go to Dashboard
                            </a>
                        </div>
                    @elseif($isAlreadyWorker)
                        <form action="{{ route('worker.agency-invitation.accept', $invitation->token) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition font-semibold">
                                Accept Invitation
                            </button>
                        </form>
                        <form action="{{ route('worker.agency-invitation.decline', $invitation->token) }}" method="POST" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition font-medium">
                                Decline
                            </button>
                        </form>
                    @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                            <p class="text-yellow-800">You need a worker account to join this agency.</p>
                            <a href="{{ route('worker.register.agency-invite', ['token' => $invitation->token]) }}" class="text-brand-600 hover:text-brand-700 font-medium mt-2 inline-block">
                                Create Worker Account
                            </a>
                        </div>
                    @endif
                @else
                    <div class="space-y-3">
                        <a href="{{ route('worker.register.agency-invite', ['token' => $invitation->token]) }}"
                           class="block w-full py-3 bg-brand-600 text-white rounded-xl hover:bg-brand-700 transition font-semibold text-center">
                            Accept & Create Account
                        </a>
                        <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                           class="block w-full py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition font-medium text-center">
                            Already have an account? Log in
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-sm text-gray-500">
                Questions? <a href="{{ route('contact') }}" class="text-brand-600 hover:text-brand-700">Contact Support</a>
            </p>
        </div>
    </div>
</div>
@endsection
