@extends('layouts.marketing')

@section('title', 'Access Denied - OvertimeStaff')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- Error Icon -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
            </div>

            <!-- Title -->
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Access Denied
            </h2>

            <!-- Description -->
            <p class="mt-2 text-sm text-gray-600">
                You don't have permission to access this page. This dashboard is for {{ ucfirst(request('intended_role', 'this user type')) }} accounts only.
            </p>

            <!-- Current User Info -->
            @if(auth()->check())
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-700">
                        <strong>You are logged in as:</strong> {{ auth()->user()->first_name }} {{ auth()->user()->last_name }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        <strong>Account Type:</strong> {{ ucfirst(auth()->user()->user_type ?? 'Not set') }}
                    </p>
                </div>
            @endif

            <!-- Actions -->
            <div class="mt-6 space-y-3">
                <!-- Go to Correct Dashboard -->
                @if(auth()->check() && auth()->user()->user_type)
                    <a href="{{ auth()->user()->user_type === 'worker' ? route('dashboard.worker') : (auth()->user()->user_type === 'business' ? route('dashboard.company') : route('dashboard.agency')) }}"
                       class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Go to My Dashboard
                    </a>
                @endif

                <!-- Contact Support -->
                <a href="{{ route('contact') }}"
                   class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    Contact Support
                </a>

                <!-- Logout -->
                @if(auth()->check())
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Sign Out & Try Different Account
                        </button>
                    </form>
                @endif
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500">
                    If you believe this is an error, please contact our support team.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection