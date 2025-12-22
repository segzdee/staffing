# 404 Error Troubleshooting Guide

## Common Causes of 404 Errors

### 1. Missing Vite Assets (Most Common)
**Symptom**: JavaScript/CSS files return 404
**Solution**: Rebuild assets on Laravel Cloud
```bash
npm run build
```

### 2. Missing Storage Symlink
**Symptom**: Uploaded images/files return 404
**Solution**: Create storage symlink
```bash
php artisan storage:link
```

### 3. Missing API Endpoints
**Symptom**: API calls return 404
**Check**: Verify route exists
```bash
php artisan route:list | grep [endpoint]
```

### 4. Route Cache Issues
**Symptom**: Routes not found after deployment
**Solution**: Clear and rebuild route cache
```bash
php artisan route:clear
php artisan route:cache
```

### 5. Missing Static Files
**Symptom**: Images, fonts, or other static assets return 404
**Check**: Verify files exist in `public/` directory

## Quick Fixes for Laravel Cloud

### Step 1: Rebuild Assets
Run in Laravel Cloud console:
```bash
npm run build
```

### Step 2: Create Storage Symlink
Run in Laravel Cloud console:
```bash
php artisan storage:link
```

### Step 3: Clear All Caches
Run in Laravel Cloud console:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: Verify Routes
Check if specific route exists:
```bash
php artisan route:list | grep [route-name]
```

## Identifying the 404 Source

### Browser Developer Tools
1. Open browser DevTools (F12)
2. Go to Network tab
3. Reload the page
4. Look for red entries (404 status)
5. Check the URL that's failing

### Common 404 Patterns

**Vite Assets**:
- `/build/assets/app-*.js` - Missing JS file
- `/build/assets/app-*.css` - Missing CSS file
- `/build/manifest.json` - Missing manifest

**Storage Files**:
- `/storage/uploads/*` - Missing storage symlink
- `/storage/app/public/*` - Files not accessible

**API Endpoints**:
- `/api/*` - Route not defined or cache issue

**Static Assets**:
- `/images/*` - Missing image files
- `/js/*` - Missing JavaScript files
- `/css/*` - Missing CSS files

## Laravel Cloud Specific

### Assets Not Deploying
Laravel Cloud should auto-build assets, but if not:
1. Check build logs in Laravel Cloud
2. Ensure `package.json` has build script
3. Verify Node.js version compatibility

### Storage Symlink
The storage symlink needs to be created on each deployment:
- Add to deployment script or run manually
- Command: `php artisan storage:link`

## Verification

After fixes, verify:
1. Assets load: Check Network tab for 200 status
2. Images load: Check uploaded file URLs
3. API works: Test API endpoints
4. Routes work: Test page navigation
