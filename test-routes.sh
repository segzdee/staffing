#!/bin/bash

# OvertimeStaff Route Testing Script
# This script tests all Phase 2 routes to ensure they're accessible

echo "============================================"
echo "OvertimeStaff Route Testing Script"
echo "============================================"
echo ""

# Configuration
BASE_URL="${BASE_URL:-http://localhost:8000}"
WORKER_TOKEN=""
BUSINESS_TOKEN=""
AGENCY_TOKEN=""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
PASSED=0
FAILED=0

# Function to test a route
test_route() {
    local method=$1
    local route=$2
    local description=$3
    local expected_status=$4
    local token=$5

    echo -n "Testing: $description... "

    if [ -z "$token" ]; then
        response=$(curl -s -o /dev/null -w "%{http_code}" -X $method "$BASE_URL$route")
    else
        response=$(curl -s -o /dev/null -w "%{http_code}" -X $method "$BASE_URL$route" -H "Authorization: Bearer $token")
    fi

    if [ "$response" == "$expected_status" ]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $response)"
        ((PASSED++))
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_status, got $response)"
        ((FAILED++))
    fi
}

echo "================================================"
echo "Phase 1: Public Routes (No Authentication)"
echo "================================================"
echo ""

test_route "GET" "/" "Homepage" "200"
test_route "GET" "/login" "Login page" "200"
test_route "GET" "/signup" "Signup page" "200"
test_route "GET" "/shifts" "Browse shifts (guest)" "200"

echo ""
echo "================================================"
echo "Phase 2: Worker Routes (Requires Worker Token)"
echo "================================================"
echo ""

if [ -z "$WORKER_TOKEN" ]; then
    echo -e "${YELLOW}⚠ SKIP: WORKER_TOKEN not set. Set it to test worker routes.${NC}"
    echo "   Example: export WORKER_TOKEN='your_token_here'"
    echo ""
else
    test_route "GET" "/worker/dashboard" "Worker dashboard" "200" "$WORKER_TOKEN"
    test_route "GET" "/worker/applications" "Worker applications" "200" "$WORKER_TOKEN"
    test_route "GET" "/worker/applications?status=pending" "Worker pending applications" "200" "$WORKER_TOKEN"
    test_route "GET" "/worker/assignments" "Worker assignments" "200" "$WORKER_TOKEN"
    test_route "GET" "/worker/assignments?status=assigned" "Worker upcoming assignments" "200" "$WORKER_TOKEN"
    echo ""
fi

echo "================================================"
echo "Phase 3: Business Routes (Requires Business Token)"
echo "================================================"
echo ""

if [ -z "$BUSINESS_TOKEN" ]; then
    echo -e "${YELLOW}⚠ SKIP: BUSINESS_TOKEN not set. Set it to test business routes.${NC}"
    echo "   Example: export BUSINESS_TOKEN='your_token_here'"
    echo ""
else
    test_route "GET" "/business/dashboard" "Business dashboard" "200" "$BUSINESS_TOKEN"
    test_route "GET" "/business/shifts" "Business shifts" "200" "$BUSINESS_TOKEN"
    test_route "GET" "/business/applications" "Business applications" "200" "$BUSINESS_TOKEN"
    test_route "GET" "/shifts/create" "Post shift form" "200" "$BUSINESS_TOKEN"
    echo ""
fi

echo "================================================"
echo "Phase 4: Agency Routes (Requires Agency Token)"
echo "================================================"
echo ""

if [ -z "$AGENCY_TOKEN" ]; then
    echo -e "${YELLOW}⚠ SKIP: AGENCY_TOKEN not set. Set it to test agency routes.${NC}"
    echo "   Example: export AGENCY_TOKEN='your_token_here'"
    echo ""
else
    test_route "GET" "/agency/dashboard" "Agency dashboard" "200" "$AGENCY_TOKEN"
    test_route "GET" "/agency/workers" "Agency workers" "200" "$AGENCY_TOKEN"
    test_route "GET" "/agency/shifts/browse" "Agency browse shifts" "200" "$AGENCY_TOKEN"
    test_route "GET" "/agency/assignments" "Agency assignments" "200" "$AGENCY_TOKEN"
    test_route "GET" "/agency/commissions" "Agency commissions" "200" "$AGENCY_TOKEN"
    test_route "GET" "/agency/analytics" "Agency analytics" "200" "$AGENCY_TOKEN"
    echo ""
fi

echo "================================================"
echo "Phase 5: Access Control Tests"
echo "================================================"
echo ""

if [ -n "$WORKER_TOKEN" ]; then
    echo "Testing worker access to business routes (should be denied):"
    test_route "GET" "/business/dashboard" "Worker→Business dashboard" "403" "$WORKER_TOKEN"
    echo ""
fi

if [ -n "$BUSINESS_TOKEN" ]; then
    echo "Testing business access to worker routes (should be denied):"
    test_route "GET" "/worker/dashboard" "Business→Worker dashboard" "403" "$BUSINESS_TOKEN"
    echo ""
fi

echo "================================================"
echo "Test Summary"
echo "================================================"
echo ""
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo "Total: $((PASSED + FAILED))"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed!${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Please review the output above.${NC}"
    exit 1
fi
