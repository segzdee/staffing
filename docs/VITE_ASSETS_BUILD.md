# Vite Assets Build Guide

## Issue

502 errors can occur on pages when Vite assets are not built in production.

## Solution

### For Laravel Cloud

Run these commands in Laravel Cloud console after deployment:

```bash
# Build production assets
npm run build

# Verify assets were created
ls -la public/build/assets/

# Clear caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Automatic Fallback

The application includes automatic fallback to Tailwind CDN when:
- `public/build/manifest.json` doesn't exist, OR
- `public/build/assets/` directory doesn't exist

This prevents 502 errors, but you should still build assets for optimal performance.

### Build Process

1. **Development**: `npm run dev` (with hot reload)
2. **Production**: `npm run build` (optimized build)

### Verification

Check if assets are built:
```bash
# Check manifest
cat public/build/manifest.json

# Check assets directory
ls -la public/build/assets/
```

### Troubleshooting

If you see 502 errors on pages:
1. Check if assets are built: `ls public/build/assets/`
2. If missing, run: `npm run build`
3. Clear view cache: `php artisan view:clear`
4. Hard refresh browser (Cmd+Shift+R)
