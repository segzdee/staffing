<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Preferences - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Email Preferences</h1>
            <p class="text-gray-600 mt-2">Manage your email subscription settings</p>
        </div>

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
            {{ session('error') }}
        </div>
        @endif

        <form action="{{ route('email.unsubscribe.process', $token) }}" method="POST" class="space-y-6">
            @csrf

            <div class="space-y-4">
                @if($category)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm text-yellow-800">
                        You're about to unsubscribe from <strong>{{ str_replace('_', ' ', $category) }}</strong> emails.
                    </p>
                    <input type="hidden" name="category" value="{{ $category }}">
                </div>
                @endif

                <div class="space-y-3">
                    <h3 class="text-sm font-medium text-gray-900">Current Preferences:</h3>
                    @foreach($preferences->getAllPreferences() as $key => $pref)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                        <span class="text-sm text-gray-700">{{ $pref['label'] }}</span>
                        @if($pref['enabled'])
                        <span class="px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">Subscribed</span>
                        @else
                        <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Unsubscribed</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6 space-y-3">
                @if($category)
                <button type="submit" class="w-full px-4 py-3 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                    Unsubscribe from {{ str_replace('_', ' ', $category) }}
                </button>
                @endif

                <button type="submit" name="unsubscribe_all" value="1" class="w-full px-4 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors">
                    Unsubscribe from All Emails
                </button>

                <a href="{{ config('app.url') }}" class="block text-center text-sm text-gray-600 hover:text-gray-900">
                    Cancel and return to {{ config('app.name') }}
                </a>
            </div>
        </form>

        <div class="mt-8 text-center text-xs text-gray-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
