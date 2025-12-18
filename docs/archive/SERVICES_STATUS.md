# Services Status ✅

## Current Status

### ✅ Reverb Server
- **Status**: Running
- **Port**: 8080
- **PID**: 17436
- **URL**: http://localhost:8080
- **Command**: `php artisan reverb:start`

### ✅ Laravel Development Server
- **Status**: Running
- **Port**: 8000
- **PID**: 17173
- **URL**: http://localhost:8000
- **Command**: `php artisan serve`

---

## Quick Commands

### Start All Services
```bash
./start-services.sh
```

### Stop All Services
```bash
./stop-services.sh
```

### Check Status
```bash
# Check Reverb
lsof -ti:8080 && echo "✅ Reverb running" || echo "❌ Reverb not running"

# Check Laravel
lsof -ti:8000 && echo "✅ Laravel running" || echo "❌ Laravel not running"
```

### Manual Start
```bash
# Start Reverb (in background)
php artisan reverb:start &

# Start Laravel (in background)
php artisan serve &
```

### Manual Stop
```bash
# Stop Reverb
kill $(lsof -ti:8080)

# Stop Laravel
kill $(lsof -ti:8000)
```

---

## Access URLs

- **Application**: http://localhost:8000
- **Reverb WebSocket**: ws://localhost:8080

---

## Testing

1. **Open browser**: http://localhost:8000
2. **Login** to the application
3. **Open console** (F12)
4. **Test toast**:
   ```javascript
   showToast({
       title: 'Test',
       message: 'Hello!',
       type: 'success'
   });
   ```

---

**Both services are running and ready!** 🎉
