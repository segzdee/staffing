@extends('layouts.dashboard')

@section('title', 'Email Preferences')
@section('page-title', 'Email Preferences')
@section('page-subtitle', 'Manage your email notification settings')

@section('content')
<div class="max-w-2xl">
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('settings.email-preferences.update') }}" method="POST">
        @csrf

        <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-200">
            <div class="px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Email Notifications</h2>
                <p class="text-sm text-gray-500 mt-1">Choose which emails you'd like to receive</p>
            </div>

            @foreach($allPreferences as $key => $preference)
            <div class="px-6 py-4">
                <label class="flex items-start gap-4 cursor-pointer">
                    <input type="checkbox" name="{{ $key }}" value="1" {{ $preference['enabled'] ? 'checked' : '' }}
                        class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <div>
                        <span class="text-sm font-medium text-gray-900">{{ $preference['label'] }}</span>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $preference['description'] }}</p>
                    </div>
                </label>
            </div>
            @endforeach
        </div>

        <div class="mt-6 flex items-center justify-between">
            <p class="text-xs text-gray-500">
                Note: Transactional emails (password resets, account security) will always be sent.
            </p>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                Save Preferences
            </button>
        </div>
    </form>
</div>
@endsection
