# Quick Testing Checklist - Phase 2 Implementation

## Pre-Test Setup ✅

1. **Start Application:**
   ```bash
   cd /Users/ots/Desktop/Staffing
   docker-compose up -d
   # OR
   php artisan serve
   ```

2. **Run Migrations (if needed):**
   ```bash
   php artisan migrate
   ```

3. **Create Test Users:**
   - Worker: Sign up at `/signup` as "Looking for work"
   - Business: Sign up at `/signup` as "Need workers"  
   - Agency: Sign up at `/signup` as "Agency"

---

## Quick Test (5 Minutes) ⚡

### 1. Navigation Test
- [ ] Login as Worker → Click all navigation links
- [ ] Login as Business → Click all navigation links  
- [ ] Login as Agency → Click all navigation links
- [ ] All links should work without 404 errors

### 2. Dashboard Test
- [ ] Visit `/worker/dashboard` (as worker) → Page loads
- [ ] Visit `/business/dashboard` (as business) → Page loads
- [ ] Visit `/agency/dashboard` (as agency) → Page loads

### 3. Views Test
- [ ] Worker: `/worker/applications` → Page loads
- [ ] Worker: `/worker/assignments` → Page loads
- [ ] Business: `/business/shifts` → Page loads
- [ ] Agency: `/agency/workers` → Page loads
- [ ] Agency: `/agency/commissions` → Page loads

### 4. Access Control Test
- [ ] As Worker, try `/business/dashboard` → Should be blocked (403)
- [ ] As Business, try `/worker/dashboard` → Should be blocked (403)

### 5. Console Check
- [ ] Open Browser DevTools → Console tab
- [ ] No JavaScript errors should appear
- [ ] Navigate through pages and check for errors

---

## Automated Route Test 🤖

Run the automated test script:

```bash
./test-routes.sh
```

Or with authentication (get tokens from browser after login):

```bash
export WORKER_TOKEN="your_worker_session_cookie"
export BUSINESS_TOKEN="your_business_session_cookie"
export AGENCY_TOKEN="your_agency_session_cookie"
./test-routes.sh
```

---

## Full Test (30 Minutes) 🔍

### Worker Flow:
1. [ ] Login as worker → Dashboard shows stats
2. [ ] Browse shifts → Can see available shifts
3. [ ] Click shift → Can view details
4. [ ] Apply to shift → Application submitted
5. [ ] Check `/worker/applications` → Application appears
6. [ ] Business accepts → Assignment appears in `/worker/assignments`
7. [ ] Check-in button appears 2 hours before shift
8. [ ] Check out after shift → Hours calculated
9. [ ] Rate business → Rating submitted

### Business Flow:
1. [ ] Login as business → Dashboard shows stats
2. [ ] Post new shift → Form works
3. [ ] View `/business/shifts` → Posted shift appears
4. [ ] Worker applies → Application appears
5. [ ] Accept application → Worker assigned
6. [ ] View assignments → Assigned worker shows
7. [ ] Shift completes → Can mark as completed

### Agency Flow:
1. [ ] Login as agency → Dashboard shows stats
2. [ ] Add worker → Worker added to pool
3. [ ] Browse shifts → Can see available shifts
4. [ ] Assign worker → Assignment created
5. [ ] Check `/agency/assignments` → Assignment appears
6. [ ] Shift completes → Commission calculated
7. [ ] Check `/agency/commissions` → Commission appears
8. [ ] Check `/agency/analytics` → Charts display

### Messaging Flow:
1. [ ] From shift page, click "Message Business/Worker"
2. [ ] Should go to `/messages/new?to=X&shift_id=Y`
3. [ ] Shift context card displays
4. [ ] Send message → Message sent
5. [ ] Go to `/shifts/{id}/messages` → Message thread displays
6. [ ] Participants list shows all members
7. [ ] Send another message → Works via AJAX

---

## Issues to Watch For ⚠️

- **404 Errors:** Route not found = Missing route definition
- **403 Errors:** Forbidden = Middleware blocking access (correct for cross-role access)
- **500 Errors:** Server error = Check Laravel logs at `storage/logs/laravel.log`
- **Blank Pages:** Missing view or controller method
- **JavaScript Errors:** Check browser console
- **Database Errors:** Missing migrations or relationships

---

## Quick Fixes 🔧

**If you see 404 on dashboard routes:**
```bash
php artisan route:clear
php artisan route:cache
```

**If you see "Class not found":**
```bash
composer dump-autoload
```

**If database errors:**
```bash
php artisan migrate:fresh
```

---

## Success Criteria ✓

Phase 2 is ready when:
- [ ] All navigation links work
- [ ] All dashboards load without errors
- [ ] No console JavaScript errors
- [ ] Access control blocks unauthorized users
- [ ] Can complete worker → business flow end-to-end
- [ ] Messaging system works for shifts

---

## Next: Phase 3 🚀

Once all tests pass, move to **Phase 3: Payment System & Instant Payouts**
