<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Page Expired | OvertimeStaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="text-center">
            <h1 class="text-9xl font-bold text-gray-200">419</h1>
            <h2 class="text-3xl font-semibold text-gray-900 mt-4">Page Expired</h2>
            <p class="text-gray-600 mt-4 max-w-md">
                Your session has expired due to inactivity. Please refresh the page and try again.
            </p>
            <div class="mt-8 flex items-center justify-center space-x-4">
                <a href="javascript:location.reload()" class="px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                    Refresh Page
                </a>
                <a href="{{ route('home') }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Go Home
                </a>
            </div>
            <p class="text-sm text-gray-500 mt-6">
                This usually happens when you've been on a page for too long without activity.
            </p>
        </div>
    </div>
</body>
</html>
