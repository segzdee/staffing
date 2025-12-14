<?php

/**
 * Login Redirect Test Script
 * Tests that each user type redirects to the correct dashboard
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "========================================\n";
echo "Login Redirect Test\n";
echo "========================================\n\n";

// Test user types
$userTypes = [
    'worker' => 'worker.dashboard',
    'business' => 'business.dashboard',
    'agency' => 'agency.dashboard',
];

// Test admin (uses role, not user_type)
$adminUser = User::where('role', 'admin')->first();
if ($adminUser) {
    echo "Testing Admin Redirect:\n";
    echo "  User ID: {$adminUser->id}\n";
    echo "  Email: {$adminUser->email}\n";
    echo "  Role: {$adminUser->role}\n";
    
    // Simulate login
    Auth::login($adminUser);
    $controller = new LoginController(app('auth'));
    $request = Request::create('/login', 'POST');
    
    $redirect = $controller->authenticated($request, $adminUser);
    
    if ($redirect && $redirect->getTargetUrl() === route('admin.dashboard')) {
        echo "  ✓ PASS: Admin redirects to admin.dashboard\n";
    } else {
        echo "  ✗ FAIL: Admin should redirect to admin.dashboard\n";
        echo "  Actual redirect: " . ($redirect ? $redirect->getTargetUrl() : 'null') . "\n";
    }
    echo "\n";
    
    Auth::logout();
}

// Test each user type
foreach ($userTypes as $userType => $expectedRoute) {
    $user = User::where('user_type', $userType)->where('status', 'active')->first();
    
    if ($user) {
        echo "Testing {$userType} Redirect:\n";
        echo "  User ID: {$user->id}\n";
        echo "  Email: {$user->email}\n";
        echo "  User Type: {$user->user_type}\n";
        
        // Simulate login
        Auth::login($user);
        $controller = new LoginController(app('auth'));
        $request = Request::create('/login', 'POST');
        
        $redirect = $controller->authenticated($request, $user);
        
        $expectedUrl = route($expectedRoute);
        $actualUrl = $redirect ? $redirect->getTargetUrl() : 'null';
        
        if ($redirect && $redirect->getTargetUrl() === $expectedUrl) {
            echo "  ✓ PASS: {$userType} redirects to {$expectedRoute}\n";
        } else {
            echo "  ✗ FAIL: {$userType} should redirect to {$expectedRoute}\n";
            echo "  Expected: {$expectedUrl}\n";
            echo "  Actual: {$actualUrl}\n";
        }
        echo "\n";
        
        Auth::logout();
    } else {
        echo "⚠ SKIP: No {$userType} user found in database\n\n";
    }
}

echo "========================================\n";
echo "Test Complete\n";
echo "========================================\n";
