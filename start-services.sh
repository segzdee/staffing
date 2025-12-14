#!/bin/bash
# Start all required services for OvertimeStaff

echo "🚀 Starting OvertimeStaff Services..."
echo ""

# Check if Reverb is already running
if lsof -ti:8080 > /dev/null 2>&1; then
    echo "⚠️  Reverb server already running on port 8080"
    echo "   To restart, kill the process first: kill \$(lsof -ti:8080)"
else
    echo "📡 Starting Reverb server on port 8080..."
    cd "$(dirname "$0")"
    php artisan reverb:start > /dev/null 2>&1 &
    REVERB_PID=$!
    sleep 2
    if lsof -ti:8080 > /dev/null 2>&1; then
        echo "✅ Reverb server started (PID: $REVERB_PID)"
    else
        echo "❌ Failed to start Reverb server"
    fi
fi

echo ""

# Check if Laravel server is already running
if lsof -ti:8000 > /dev/null 2>&1; then
    echo "⚠️  Laravel server already running on port 8000"
    echo "   To restart, kill the process first: kill \$(lsof -ti:8000)"
else
    echo "🌐 Starting Laravel development server on port 8000..."
    cd "$(dirname "$0")"
    php artisan serve --host=127.0.0.1 --port=8000 > /dev/null 2>&1 &
    LARAVEL_PID=$!
    sleep 2
    if lsof -ti:8000 > /dev/null 2>&1; then
        echo "✅ Laravel server started (PID: $LARAVEL_PID)"
    else
        echo "❌ Failed to start Laravel server"
    fi
fi

echo ""
echo "📋 Service Status:"
echo "   Reverb:   http://localhost:8080"
echo "   Laravel:  http://localhost:8000"
echo ""
echo "💡 To stop services:"
echo "   kill \$(lsof -ti:8080)  # Stop Reverb"
echo "   kill \$(lsof -ti:8000)  # Stop Laravel"
echo ""
echo "✅ All services started!"
