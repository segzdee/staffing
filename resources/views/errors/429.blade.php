<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>429 - Too Many Requests | OvertimeStaff</title>
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
            <h1 class="text-9xl font-bold text-gray-200">429</h1>
            <h2 class="text-3xl font-semibold text-gray-900 mt-4">Too Many Requests</h2>
            <p class="text-gray-600 mt-4 max-w-md">
                You've made too many requests in a short period of time. Please slow down and try again in a moment.
            </p>
            <div class="mt-8 flex items-center justify-center space-x-4">
                <a href="javascript:history.back()" class="px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                    Go Back
                </a>
                <a href="{{ route('home') }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Go Home
                </a>
            </div>
            <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg max-w-md mx-auto">
                <p class="text-sm text-yellow-800">
                    <strong>Tip:</strong> If you're seeing this frequently, please wait 60 seconds before making another request.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
