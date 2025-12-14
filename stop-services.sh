#!/bin/bash
# Stop all OvertimeStaff services

echo "🛑 Stopping OvertimeStaff Services..."
echo ""

# Stop Reverb
if lsof -ti:8080 > /dev/null 2>&1; then
    REVERB_PID=$(lsof -ti:8080)
    kill $REVERB_PID 2>/dev/null
    sleep 1
    if lsof -ti:8080 > /dev/null 2>&1; then
        kill -9 $REVERB_PID 2>/dev/null
    fi
    echo "✅ Reverb server stopped"
else
    echo "ℹ️  Reverb server not running"
fi

# Stop Laravel
if lsof -ti:8000 > /dev/null 2>&1; then
    LARAVEL_PID=$(lsof -ti:8000)
    kill $LARAVEL_PID 2>/dev/null
    sleep 1
    if lsof -ti:8000 > /dev/null 2>&1; then
        kill -9 $LARAVEL_PID 2>/dev/null
    fi
    echo "✅ Laravel server stopped"
else
    echo "ℹ️  Laravel server not running"
fi

echo ""
echo "✅ All services stopped!"
