#!/bin/bash

# Security Fixes Verification Script
# This script tests all the security fixes implemented

echo "=========================================="
echo "Security Fixes Verification Test"
echo "=========================================="
echo ""

cd /Users/ots/Desktop/Staffing

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Check admin route prefix
echo "Test 1: Verifying admin route prefix..."
if php artisan route:list | grep -q "panel/admin"; then
    echo -e "${GREEN}✓ PASS${NC} - Admin routes use /panel/admin prefix"
else
    echo -e "${RED}✗ FAIL${NC} - Admin routes not using /panel/admin prefix"
fi
echo ""

# Test 2: Check dev routes protection
echo "Test 2: Verifying dev routes are protected..."
if grep -q "app()->environment('local', 'development'" routes/web.php; then
    echo -e "${GREEN}✓ PASS${NC} - Dev routes have environment check"
else
    echo -e "${RED}✗ FAIL${NC} - Dev routes missing environment check"
fi
echo ""

# Test 3: Check clear cache route protection
echo "Test 3: Verifying clear cache route is protected..."
if grep -A 2 "/clear-cache" routes/web.php | grep -q "environment\|middleware"; then
    echo -e "${GREEN}✓ PASS${NC} - Clear cache route is protected"
else
    echo -e "${RED}✗ FAIL${NC} - Clear cache route not protected"
fi
echo ""

# Test 4: Check rate limiting configuration
echo "Test 4: Verifying rate limiting configuration..."
if grep -q "maxAttempts = 5" app/Http/Controllers/Auth/LoginController.php; then
    echo -e "${GREEN}✓ PASS${NC} - Rate limiting configured (5 attempts)"
else
    echo -e "${RED}✗ FAIL${NC} - Rate limiting not configured"
fi

if grep -q "decayMinutes = 15" app/Http/Controllers/Auth/LoginController.php; then
    echo -e "${GREEN}✓ PASS${NC} - Rate limiting lockout configured (15 minutes)"
else
    echo -e "${RED}✗ FAIL${NC} - Rate limiting lockout not configured"
fi
echo ""

# Test 5: Check security logging
echo "Test 5: Verifying security logging..."
if grep -q "Log::channel('security')" app/Http/Controllers/Auth/LoginController.php; then
    echo -e "${GREEN}✓ PASS${NC} - Security logging implemented"
else
    echo -e "${RED}✗ FAIL${NC} - Security logging not implemented"
fi

if grep -q "'security'" config/logging.php; then
    echo -e "${GREEN}✓ PASS${NC} - Security log channel configured"
else
    echo -e "${RED}✗ FAIL${NC} - Security log channel not configured"
fi
echo ""

# Test 6: Check Authenticate middleware URL preservation
echo "Test 6: Verifying Authenticate middleware URL preservation..."
if grep -q "url.intended" app/Http/Middleware/Authenticate.php; then
    echo -e "${GREEN}✓ PASS${NC} - Intended URL preservation implemented"
else
    echo -e "${RED}✗ FAIL${NC} - Intended URL preservation not implemented"
fi
echo ""

# Test 7: Check post-login redirect by user type
echo "Test 7: Verifying post-login redirect by user type..."
if grep -q "authenticated.*Request.*user" app/Http/Controllers/Auth/LoginController.php; then
    echo -e "${GREEN}✓ PASS${NC} - Post-login redirect by user type implemented"
else
    echo -e "${RED}✗ FAIL${NC} - Post-login redirect by user type not implemented"
fi
echo ""

# Test 8: Check session security settings
echo "Test 8: Verifying session security settings..."
if grep -q "SESSION_SECURE_COOKIE.*production" config/session.php; then
    echo -e "${GREEN}✓ PASS${NC} - Secure cookie setting configured"
else
    echo -e "${YELLOW}⚠ WARN${NC} - Secure cookie setting may need review"
fi

if grep -q "SESSION_SAME_SITE" config/session.php; then
    echo -e "${GREEN}✓ PASS${NC} - Same-site cookie setting configured"
else
    echo -e "${YELLOW}⚠ WARN${NC} - Same-site cookie setting may need review"
fi
echo ""

# Test 9: Check password reset redirect
echo "Test 9: Verifying password reset redirect..."
if grep -q "redirectTo = '/login'" app/Http/Controllers/Auth/ResetPasswordController.php; then
    echo -e "${GREEN}✓ PASS${NC} - Password reset redirects to login"
else
    echo -e "${RED}✗ FAIL${NC} - Password reset redirect not fixed"
fi
echo ""

# Test 10: Check logout functionality
echo "Test 10: Verifying logout functionality..."
if grep -q "function logout" app/Http/Controllers/Auth/LoginController.php; then
    echo -e "${GREEN}✓ PASS${NC} - Logout method implemented"
else
    echo -e "${RED}✗ FAIL${NC} - Logout method not implemented"
fi
echo ""

# Test 11: Check security log file exists
echo "Test 11: Checking security log file..."
if [ -f "storage/logs/security.log" ] || [ -f "storage/logs/security-$(date +%Y-%m-%d).log" ]; then
    echo -e "${GREEN}✓ PASS${NC} - Security log file exists"
    echo "Recent security log entries:"
    tail -5 storage/logs/security*.log 2>/dev/null | head -10 || echo "No log entries yet"
else
    echo -e "${YELLOW}⚠ WARN${NC} - Security log file not created yet (will be created on first log entry)"
fi
echo ""

# Test 12: Verify route protection
echo "Test 12: Verifying route protection..."
echo "Checking admin routes:"
php artisan route:list | grep "panel/admin" | head -3
echo ""

echo "=========================================="
echo "Verification Complete"
echo "=========================================="
