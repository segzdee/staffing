<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Credentials - OvertimeStaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 5px rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 20px rgba(239, 68, 68, 0.8); }
        }
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">🔐 Development Credentials</h1>
                    <p class="text-gray-600 mt-2">Quick access to all OvertimeStaff dashboards for testing</p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                        ⚠️ Development Only
                    </span>
                </div>
            </div>
        </div>

        <!-- Warning Banner -->
        @if(!app()->environment('local', 'development'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            <p class="font-bold">⚠️ Security Warning</p>
            <p>This page is only available in local/development environments.</p>
        </div>
        @endif

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
            {{ session('error') }}
        </div>
        @endif

        <!-- Credentials Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($credentials as $type => $cred)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-lg mr-2">
                                    @if($type === 'worker') 👷
                                    @elseif($type === 'business') 🏢
                                    @elseif($type === 'agency') 🏛️
                                    @elseif($type === 'agent') 🤖
                                    @elseif($type === 'admin') 👨‍💼
                                    @endif
                                </span>
                                <span class="font-semibold text-gray-900">{{ $cred['name'] }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $cred['email'] }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $cred['password'] }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($cred['exists'])
                                <div>
                                    <div class="text-sm text-gray-900">
                                        {{ $cred['expires_at']->format('M d, Y H:i') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cred['expires_in'] }}
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-400">Not created</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(!$cred['exists'])
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-700">
                                    Missing
                                </span>
                            @elseif($cred['is_expired'])
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-200 text-red-700 pulse-glow">
                                    Expired
                                </span>
                            @elseif($cred['days_remaining'] <= 1)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-200 text-yellow-700">
                                    Expiring Soon
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-200 text-green-700">
                                    Active ({{ $cred['days_remaining'] }}d)
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if($cred['exists'] && !$cred['is_expired'])
                                @if($type === 'agent')
                                    <div class="space-y-1">
                                        <a href="{{ route('dev.login', $type) }}" 
                                           class="inline-block px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700 transition">
                                            Quick Login →
                                        </a>
                                        <p class="text-xs text-gray-500">Uses API endpoints</p>
                                    </div>
                                @else
                                    <a href="{{ route('dev.login', $type) }}" 
                                       class="inline-block px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700 transition">
                                        Quick Login →
                                    </a>
                                @endif
                            @else
                                <span class="text-sm text-gray-400">N/A</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Actions -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🔧 Management Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Refresh Credentials</h3>
                    <p class="text-sm text-gray-600 mb-3">Re-run the seeder to refresh expiration dates (extends by 7 days)</p>
                    <form action="{{ route('dev.credentials') }}" method="POST" onsubmit="return confirm('This will refresh all dev credentials. Continue?');">
                        @csrf
                        <input type="hidden" name="action" value="refresh">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded hover:bg-green-700">
                            Run Seeder
                        </button>
                    </form>
                </div>
                <div class="border border-gray-200 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">Cleanup Expired</h3>
                    <p class="text-sm text-gray-600 mb-3">Manually run cleanup command to remove expired accounts</p>
                    <code class="block text-xs bg-gray-100 px-3 py-2 rounded mb-2">php artisan dev:cleanup-expired</code>
                    <p class="text-xs text-gray-500">Or use --dry-run to preview</p>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
            <h3 class="font-semibold text-blue-900 mb-2">ℹ️ Information</h3>
            <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                <li>All dev accounts expire automatically after 7 days</li>
                <li>Expired accounts are cleaned up daily at 3:00 AM</li>
                <li>You can refresh credentials anytime by re-running the seeder</li>
                <li>Quick login bypasses authentication for faster testing</li>
                <li>These routes only work in local/development environments</li>
            </ul>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <p>OvertimeStaff Development Credentials | Auto-expires in 7 days</p>
        </div>
    </div>

    <script>
        // Auto-refresh page every 60 seconds to update countdown
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>

