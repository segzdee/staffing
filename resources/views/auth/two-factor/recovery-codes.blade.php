@extends('layouts.authenticated')

@section('title', 'Recovery Codes - OvertimeStaff')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-sm border p-6" style="border-color: hsl(240 5.9% 90%);">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Recovery Codes</h1>
            <p class="mt-2 text-sm text-gray-600">
                These codes can be used to access your account if you lose your authenticator device.
            </p>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        <!-- Warning -->
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-red-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-red-800">Save these codes now!</h4>
                    <p class="mt-1 text-sm text-red-700">
                        These codes will only be shown once. Store them in a secure location like a password manager.
                        If you lose your authenticator device and these codes, you will lose access to your account.
                    </p>
                </div>
            </div>
        </div>

        <!-- Recovery Codes Grid -->
        <div class="mb-8" x-data="{ copied: false }">
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    @foreach($recoveryCodes as $code)
                    <div class="font-mono text-sm bg-white px-3 py-2 rounded border text-center" style="border-color: hsl(240 5.9% 90%);">
                        {{ $code }}
                    </div>
                    @endforeach
                </div>

                <!-- Copy All Button -->
                <div class="flex justify-center gap-3">
                    <button
                        type="button"
                        @click="navigator.clipboard.writeText('{{ implode("\n", $recoveryCodes) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-50 transition-colors"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        <svg x-show="!copied" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <svg x-show="copied" class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="copied ? 'Copied!' : 'Copy All Codes'"></span>
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg hover:bg-gray-50 transition-colors"
                        style="border-color: hsl(240 5.9% 90%);"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print Codes
                    </button>
                </div>
            </div>
        </div>

        <!-- Usage Info -->
        <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-600 mr-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-blue-800">How to use recovery codes</h4>
                    <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                        <li>Each code can only be used <strong>once</strong></li>
                        <li>When logging in, click "Use a Recovery Code" instead of entering your authenticator code</li>
                        <li>After using a recovery code, generate new codes immediately</li>
                        <li>Store codes in a password manager or print and keep in a secure location</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Regenerate Codes -->
        <div class="mb-8 pt-6 border-t" style="border-color: hsl(240 5.9% 90%);">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Generate New Codes</h3>
            <p class="text-sm text-gray-500 mb-4">
                Regenerating recovery codes will invalidate all existing codes. Make sure you save the new codes before generating.
            </p>
            <form action="{{ route('two-factor.recovery-codes.regenerate') }}" method="POST" x-data="{ showPassword: false, confirmed: false }">
                @csrf
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            id="password"
                            required
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 @error('password') border-red-500 @enderror"
                            style="border-color: hsl(240 5.9% 90%); max-width: 300px;"
                        >
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="confirmed" class="w-4 h-4 text-gray-900 border-gray-300 rounded focus:ring-gray-900">
                        <span class="ml-2 text-sm text-gray-700">I have saved my current recovery codes</span>
                    </label>
                </div>
                <button
                    type="submit"
                    :disabled="!confirmed"
                    class="px-4 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Generate New Recovery Codes
                </button>
            </form>
        </div>

        <!-- Back Link -->
        <div class="pt-6 border-t" style="border-color: hsl(240 5.9% 90%);">
            <a href="{{ route('two-factor.index') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to 2FA Settings
            </a>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style media="print" nonce="{{ $cspNonce ?? '' }}">
    @page { margin: 2cm; }
    body * { visibility: hidden; }
    .recovery-codes-print, .recovery-codes-print * { visibility: visible; }
    .recovery-codes-print { position: absolute; left: 0; top: 0; }
</style>
@endsection
