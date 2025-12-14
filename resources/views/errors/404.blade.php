<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | OvertimeStaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="text-center">
            <h1 class="text-9xl font-bold text-gray-200">404</h1>
            <h2 class="text-3xl font-semibold text-gray-900 mt-4">Page Not Found</h2>
            <p class="text-gray-600 mt-4 max-w-md">
                Sorry, we couldn't find the page you're looking for. The link might be broken or the page may have been removed.
            </p>
            <div class="mt-8 flex items-center justify-center space-x-4">
                <a href="{{ route('home') }}" class="px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                    Go Home
                </a>
                <a href="javascript:history.back()" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    Go Back
                </a>
            </div>
        </div>
    </div>
</body>
</html>
