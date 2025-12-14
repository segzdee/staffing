# Quick Start Testing Guide

## 🚀 Step 1: Clear Cache (REQUIRED)

You need to manually run these commands since PHP is not in your system PATH.

### Find Your PHP Installation

**Common locations:**
- XAMPP: `C:\xampp\php\php.exe`
- WAMP: `C:\wamp64\bin\php\php[version]\php.exe`
- Laragon: `C:\laragon\bin\php\php-[version]\php.exe`
- Manual install: `C:\php\php.exe`

### Run These Commands

Replace `C:\xampp\php\php.exe` with your actual PHP path:

```bash
cd C:\Users\User\overtimestaff_prod

C:\xampp\php\php.exe artisan cache:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan view:clear
```

### Expected Output:
```
Application cache cleared!
Configuration cache cleared!
Route cache cleared!
Compiled views cleared!
```

---

## 🧪 Step 2: Quick Smoke Test (5 minutes)

Test these URLs in your browser after logging in:

### Critical Routes (Must Work):
1. ✅ **Dashboard**: `http://yoursite.local/dashboard`
2. ✅ **Profile**: `http://yoursite.local/{your-username}`
3. ✅ **Settings**: `http://yoursite.local/settings/page`
4. ✅ **Notifications**: `http://yoursite.local/notifications`
5. ✅ **My Subscriptions**: `http://yoursite.local/my/subscriptions`

### If ANY of these fail:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console (F12 → Console tab)
3. Note the exact error message
4. See **Common Errors** section below

---

## 🐛 Common Errors & Quick Fixes

### Error: "Class not found"
```bash
C:\xampp\php\php.exe composer.phar dump-autoload
C:\xampp\php\php.exe artisan clear-compiled
```

### Error: "Route not found" or 404
```bash
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan route:cache
```

### Error: "Method does not exist"
**Check:** Route file matches controller method name exactly (case-sensitive!)

---

## ⚡ Quick Rollback (If Needed)

If something is broken and you need to revert:

```bash
cd C:\Users\User\overtimestaff_prod
copy routes\web.php.backup routes\web.php
C:\xampp\php\php.exe artisan route:clear
```

This restores the original routes.

---

## 📋 Full Testing Checklist

See `TESTING_GUIDE.md` for detailed testing instructions.

**Priority Order:**
1. Dashboard & Profiles (most used)
2. Settings (commonly used)
3. Media uploads (frequently used)
4. Subscriptions (view only - don't test payments!)
5. Everything else

---

## ✅ You're Done When...

- [ ] All 5 critical routes above work
- [ ] No errors in `storage/logs/laravel.log`
- [ ] No red errors in browser console
- [ ] Can upload avatar/cover images
- [ ] Can like/unlike posts
- [ ] Can bookmark posts

---

## 🎯 Next Steps After Testing

1. **All tests pass?**
   - Commit to git: `git add . && git commit -m "Refactor UserController into 8 focused controllers"`
   - Deploy to production during low-traffic hours

2. **Some tests fail?**
   - Note the failing routes/methods
   - Check the specific controller file for typos
   - Compare with original UserController method
   - Fix and re-test

3. **Everything broken?**
   - Restore routes: `copy routes\web.php.backup routes\web.php`
   - Clear cache again
   - Report issues with error logs

---

## 📞 Need More Help?

- **Detailed testing:** See `TESTING_GUIDE.md`
- **What was changed:** See `PHASE_1_COMPLETION_SUMMARY.md`
- **Implementation details:** See `REFACTORING_PROGRESS.md`

---

**Estimated Testing Time:** 15-30 minutes for basic smoke tests
**Recommended:** Test in development/staging before production!
