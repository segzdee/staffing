# OvertimeStaff Deployment Checklist
**Date:** December 15, 2025
**Version:** 1.0.0
**Environment:** Staging → Production

---

## ✅ Pre-Deployment Steps

### 1. Code Review & Testing
- [x] All security fixes implemented (11/11)
- [x] RoleSelection screen redesigned
- [x] Database insert failure fixed
- [x] Laravel caches cleared
- [x] Routes verified and cached
- [ ] Manual testing completed (see testing guide below)
- [ ] Code reviewed by team
- [ ] All tests passing

### 2. Environment Configuration
- [ ] Verify `.env` file has all required variables:
  ```env
  # Session Security
  SESSION_ENCRYPT=true

  # Webhook Verification
  STRIPE_WEBHOOK_SECRET=whsec_xxxxx
  PAYPAL_WEBHOOK_ID=xxxxx
  PAYPAL_CLIENT_ID=xxxxx
  PAYPAL_SECRET=xxxxx
  PAYPAL_MODE=live
  PAYSTACK_SECRET_KEY=sk_xxxxx

  # Database
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=overtimestaff
  DB_USERNAME=your_username
  DB_PASSWORD=your_secure_password

  # Application
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://yourdomain.com

  # Cache & Queue
  CACHE_DRIVER=redis
  QUEUE_CONNECTION=redis
  SESSION_DRIVER=redis
  ```

### 3. Database Preparation
- [ ] Backup current production database
  ```bash
  mysqldump -u root -p overtimestaff > backup_$(date +%Y%m%d_%H%M%S).sql
  ```
- [ ] Verify database connection
  ```bash
  php artisan db:show
  ```
- [ ] Check migration status (some migrations may show as "Pending" but tables exist - this is OK)
  ```bash
  php artisan migrate:status
  ```

---

## 🚀 Deployment Steps

### Step 1: Pull Latest Code
```bash
cd /Users/ots/Desktop/Staffing
git pull origin main
```

### Step 2: Install Dependencies
```bash
# PHP dependencies
composer install --no-dev --optimize-autoloader

# JavaScript dependencies (if updated)
npm ci
npm run build
```

### Step 3: Clear and Rebuild Caches
```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optional: Cache events
php artisan event:cache
```

### Step 4: Run Migrations (if needed)
**Note:** Some migrations may fail because tables already exist. This is expected if you previously ran migrations manually.

```bash
# Backup first!
php artisan migrate --force

# If errors occur due to existing tables, verify tables exist:
php artisan db:show
```

### Step 5: Restart Services
```bash
# Restart PHP-FPM (if using)
sudo service php8.3-fpm restart

# Restart queue workers
php artisan queue:restart

# Restart web server (choose one)
sudo service nginx restart
# OR
sudo service apache2 restart

# Restart Redis (if using)
redis-cli FLUSHALL
```

### Step 6: Verify Deployment
```bash
# Check application status
php artisan about

# Verify routes are working
php artisan route:list | grep onboarding

# Check queue status
php artisan queue:work --once --stop-when-empty
```

---

## 🧪 Post-Deployment Testing

### Critical Path Testing

#### 1. Role Selection Screen
**URL:** `/onboarding/select-role`

**Test Steps:**
1. [ ] Navigate to role selection page
2. [ ] Verify page loads without errors
3. [ ] Hover over each role card - verify hover effects
4. [ ] Click "Worker" card - verify blue border, checkmark, scale effect
5. [ ] Click "Company" card - verify previous selection deselects
6. [ ] Click "Agency" card - verify only one selected at a time
7. [ ] Verify Continue button is disabled when no selection
8. [ ] Select a role - verify Continue button becomes enabled
9. [ ] Click Continue - verify loading spinner appears
10. [ ] Verify redirect to appropriate profile page:
    - Worker → `/worker/profile/complete`
    - Business → `/business/profile/complete`
    - Agency → `/agency/profile/complete`

**Database Verification:**
```sql
-- Check user was updated
SELECT id, name, user_type, onboarding_step FROM users WHERE id = [user_id];

-- Check profile was created (check appropriate table)
SELECT * FROM worker_profiles WHERE user_id = [user_id];
SELECT * FROM business_profiles WHERE user_id = [user_id];
SELECT * FROM agency_profiles WHERE user_id = [user_id];
```

#### 2. Authentication & Security

**Password Policy (New Users):**
1. [ ] Try registering with 8-character password - should fail
2. [ ] Try password with no symbols - should fail
3. [ ] Try password with no numbers - should fail
4. [ ] Try password with no uppercase - should fail
5. [ ] Register with 12+ character complex password - should succeed

**Rate Limiting:**
1. [ ] Try login with wrong password 5 times - verify lockout message
2. [ ] Wait 1 minute - verify can try again
3. [ ] Try password reset 3 times in an hour - verify rate limit message

**Account Lockout:**
1. [ ] Log in with wrong password 5 times
2. [ ] Verify account locked for 30 minutes
3. [ ] Verify email notification sent
4. [ ] Admin: Check `/panel/admin/account-lockouts` - verify locked account appears

**2FA (Optional - for users who enable it):**
1. [ ] Navigate to `/settings/two-factor`
2. [ ] Click "Enable 2FA"
3. [ ] Scan QR code with authenticator app
4. [ ] Enter code to confirm
5. [ ] Verify recovery codes displayed
6. [ ] Log out and log back in
7. [ ] Verify 2FA code required
8. [ ] Enter valid code - verify login succeeds

**Session Security:**
1. [ ] Log in with "Remember Me" checked
2. [ ] Close browser and reopen
3. [ ] Verify still logged in
4. [ ] Clear cookies and try to access protected page
5. [ ] Verify redirected to login

**Webhook Security:**
1. [ ] Send test webhook from Stripe dashboard
2. [ ] Verify webhook received and processed
3. [ ] Check logs for signature verification
4. [ ] Try sending webhook with invalid signature - should be rejected

#### 3. Dashboard Access

**Worker Dashboard:**
1. [ ] Log in as worker
2. [ ] Verify dashboard loads at `/worker/dashboard`
3. [ ] Check all widgets load (shifts, earnings, applications)
4. [ ] Verify navigation menu items work

**Business Dashboard:**
1. [ ] Log in as business
2. [ ] Verify dashboard loads at `/business/dashboard`
3. [ ] Check all widgets load (shifts, applications, analytics)
4. [ ] Verify can create new shift (if activated)

**Agency Dashboard:**
1. [ ] Log in as agency
2. [ ] Verify dashboard loads at `/agency/dashboard`
3. [ ] Check all widgets load (workers, clients, commissions)
4. [ ] Verify worker management features work

**Admin Dashboard:**
1. [ ] Log in as admin
2. [ ] Verify dashboard loads at `/panel/admin`
3. [ ] Check account lockouts page works
4. [ ] Verify verification queue accessible
5. [ ] Check system health metrics display

---

## 🔒 Security Verification

### Security Checklist

- [ ] **Session encryption** enabled (`config/session.php` → `encrypt = true`)
- [ ] **HTTPS** enforced (check `.env` → `APP_URL` starts with `https://`)
- [ ] **HSTS header** present (production only)
- [ ] **CSP header** with nonces (check browser DevTools → Network → Headers)
- [ ] **Permissions-Policy** header present
- [ ] **Email verification** required for new users (no auto-verify)
- [ ] **Password policy** enforcing 12+ characters with complexity
- [ ] **Rate limiting** active on all auth routes
- [ ] **Account lockout** working after 5 failed attempts
- [ ] **2FA** available in user settings
- [ ] **Webhook signatures** verified (Stripe, PayPal, Paystack)
- [ ] **Remember token rotation** working
- [ ] **Development bypasses** secured (4-point check)

### Security Audit Commands

```bash
# Check security headers
curl -I https://yourdomain.com | grep -E "(Strict-Transport-Security|Content-Security-Policy|Permissions-Policy)"

# Check session encryption
php artisan tinker --execute="echo config('session.encrypt');" # Should output: true

# Verify webhook middleware registered
php artisan route:list | grep webhook

# Check rate limiters
cat app/Providers/RouteServiceProvider.php | grep -A5 "RateLimiter::for"
```

---

## 📊 Monitoring & Logs

### Log Files to Monitor

```bash
# Application logs
tail -f storage/logs/laravel.log

# Security logs
tail -f storage/logs/security.log

# Admin logs
tail -f storage/logs/admin.log

# Queue logs
tail -f storage/logs/queue.log
```

### Key Metrics to Watch (First 48 Hours)

- [ ] Failed login attempts (should see more lockouts with new 5-attempt limit)
- [ ] Password reset requests (should see fewer with stronger passwords)
- [ ] 2FA adoption rate (check how many users enable it)
- [ ] Webhook verification failures (should be zero)
- [ ] Session errors (should be minimal)
- [ ] Role selection completion rate
- [ ] Database errors on role selection (should be zero)

### Alert Thresholds

Set up alerts for:
- More than 10 failed logins per minute (potential attack)
- More than 5 webhook signature failures per hour
- Any database errors on role selection endpoint
- More than 100 account lockouts per day
- Session errors spike

---

## 🔄 Rollback Plan

If critical issues occur, use this rollback procedure:

### Quick Rollback (Code Only)
```bash
# Revert to previous release
git checkout [previous_commit_hash]
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo service php8.3-fpm restart
```

### Full Rollback (Code + Database)
```bash
# Restore database backup
mysql -u root -p overtimestaff < backup_20251215_HHMMSS.sql

# Revert code
git checkout [previous_commit_hash]
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo service php8.3-fpm restart
sudo service nginx restart
php artisan queue:restart
```

---

## 📞 Support & Troubleshooting

### Common Issues

#### Issue: "Class SelectRoleRequest not found"
**Solution:**
```bash
php artisan optimize:clear
composer dump-autoload
```

#### Issue: "CSRF token mismatch"
**Solution:**
```bash
php artisan optimize:clear
# Clear browser cookies and try again
```

#### Issue: "Session lifetime error"
**Solution:**
```bash
# Check session encryption is enabled
php artisan config:clear
php artisan cache:clear
# Have all users log out and log back in
```

#### Issue: "Webhook signature verification failed"
**Solution:**
```bash
# Verify webhook secrets in .env
# Check logs for specific provider
tail -f storage/logs/laravel.log | grep webhook
# Verify middleware is applied to routes
php artisan route:list | grep webhook
```

#### Issue: "Route [onboarding.select-role] not defined"
**Solution:**
```bash
php artisan route:clear
php artisan route:cache
php artisan route:list | grep onboarding
```

---

## ✅ Final Checklist

Before marking deployment as complete:

- [ ] All pre-deployment steps completed
- [ ] All deployment steps executed successfully
- [ ] All critical path tests passed
- [ ] Security verification completed
- [ ] Monitoring set up and working
- [ ] Team notified of new features:
  - Stronger password requirements
  - 2FA availability
  - Account lockout policy
  - New role selection screen
- [ ] Documentation updated:
  - User guide for password requirements
  - Admin guide for account lockout management
  - Developer guide for webhook configuration
- [ ] Rollback plan tested and ready
- [ ] Support team briefed on changes
- [ ] Backup retention policy confirmed

---

## 📝 Post-Deployment Notes

**Date Deployed:** _______________
**Deployed By:** _______________
**Version:** _______________

**Issues Encountered:**



**Resolution:**



**User Feedback:**



**Next Steps:**



---

## 🎉 Deployment Complete!

**Congratulations!** Your OvertimeStaff application is now deployed with:
- ✅ 11 security vulnerabilities fixed
- ✅ Beautiful new role selection screen
- ✅ Robust database operations
- ✅ Enterprise-grade authentication security
- ✅ Production-ready error handling

**Remember to:**
1. Monitor logs for first 48 hours
2. Gather user feedback on new password requirements
3. Promote 2FA adoption to users
4. Review lockout statistics weekly
5. Test webhook integrations thoroughly

**Support Contact:** [Your Support Email/Slack Channel]
**Documentation:** [Link to Your Docs]
**Issue Tracker:** [Link to GitHub/Jira]

---

*Last Updated: December 15, 2025*
