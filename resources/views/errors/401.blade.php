<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>401 - Unauthorized | OvertimeStaff</title>
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
            <h1 class="text-9xl font-bold text-gray-200">401</h1>
            <h2 class="text-3xl font-semibold text-gray-900 mt-4">Unauthorized</h2>
            <p class="text-gray-600 mt-4 max-w-md">
                You are not authorized to access this page. Please log in with valid credentials to continue.
            </p>
            <div class="mt-8 flex items-center justify-center space-x-4">
                <a href="{{ route('login') }}" class="px-6 py-3 bg-brand-600 text-white rounded-lg hover:bg-brand-700 font-medium">
                    Sign In
                </a>
                <a href="{{ route('home') }}" class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium">
                    Go Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>
