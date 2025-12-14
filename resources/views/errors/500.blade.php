<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error | OvertimeStaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="text-center">
            <h1 class="text-9xl font-bold text-gray-200">500</h1>
            <h2 class="text-3xl font-semibold text-gray-900 mt-4">Server Error</h2>
            <p class="text-gray-600 mt-4 max-w-md">
                Something went wrong on our end. We're working to fix it.
            </p>
            <a href="{{ route('home') }}" class="mt-8 inline-block px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700">
                Go Home
            </a>
        </div>
    </div>
</body>
</html>
