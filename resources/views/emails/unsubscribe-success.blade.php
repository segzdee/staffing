<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg max-w-md w-full p-8 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-2">Successfully Unsubscribed</h1>
        <p class="text-gray-600 mb-8">{{ $message }}</p>

        <div class="space-y-4">
            <p class="text-sm text-gray-500">
                You can update your preferences at any time by visiting your account settings.
            </p>

            <div class="flex flex-col gap-3">
                <a href="{{ config('app.url') }}" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    Return to {{ config('app.name') }}
                </a>

                <form action="{{ route('email.resubscribe', $preferences->unsubscribe_token) }}" method="POST">
                    @csrf
                    <button type="submit" name="category" value="marketing_emails" class="text-sm text-gray-600 hover:text-blue-600 underline">
                        Changed your mind? Resubscribe
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 text-xs text-gray-500">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
