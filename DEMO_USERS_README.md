# OVERTIMESTAFF - Demo Users Guide

## 🚀 Quick Start

### Running the Demo User Seeder

To create all demo users for testing, run:

```bash
# Option 1: Using Docker Sail
./vendor/bin/sail artisan db:seed --class=DemoUsersSeeder

# Option 2: Using Docker Compose
docker-compose exec app php artisan db:seed --class=DemoUsersSeeder

# Option 3: Using PHP directly (if not using Docker)
php artisan db:seed --class=DemoUsersSeeder
```

---

## 📧 Demo User Credentials

**All passwords: `password`**

### 👷 DEMO WORKER
- **Email:** `worker@demo.com`
- **Password:** `password`
- **Access:**
  - Worker Dashboard
  - Shift Applications
  - Calendar & Availability
  - Profile Management
  - Payment History
- **Profile Details:**
  - Name: Demo Worker
  - Skills: Customer Service, POS Systems, Food Handling
  - Hourly Rate: $25/hr
  - Rating: 4.8/5.0
  - Completed Shifts: 127
  - Reliability Score: 95%

### 🏢 DEMO BUSINESS
- **Email:** `business@demo.com`
- **Password:** `password`
- **Access:**
  - Business Dashboard
  - Post Shifts
  - Manage Applications
  - Worker Management
  - Payment & Billing
  - Analytics
- **Business Details:**
  - Business Name: Demo Restaurant & Bar
  - Type: Hospitality
  - Locations: 3
  - Rating: 4.6/5.0
  - Total Shifts Posted: 89

### 🏛️ DEMO AGENCY
- **Email:** `agency@demo.com`
- **Password:** `password`
- **Access:**
  - Agency Dashboard
  - Worker Pool Management
  - Bulk Placements
  - Advanced Matching
  - Commission Tracking
  - Multi-client Management
- **Agency Details:**
  - Agency Name: Demo Staffing Solutions
  - Specializations: Hospitality, Retail, Events
  - Worker Pool: 523 workers
  - Total Placements: 1,247
  - Rating: 4.7/5.0

### 👨‍💼 DEMO ADMIN
- **Email:** `admin@demo.com`
- **Password:** `password`
- **Access:**
  - Full Admin Panel
  - User Management (All Types)
  - Dispute Resolution
  - System Settings
  - Compliance Oversight
  - Verification Requests
  - Audit Logs
  - MFA Setup (when enabled)
- **Permissions:** Full system access

---

## 🧪 Additional Test Accounts

### Worker 2 - Sarah Johnson
- **Email:** `worker2@demo.com`
- **Password:** `password`
- **Profile:** Healthcare RN with ACLS certification
- **Hourly Rate:** $45/hr
- **Use Case:** Testing worker-to-worker interactions, shift competitions

### Business 2 - RetailMax Stores
- **Email:** `business2@demo.com`
- **Password:** `password`
- **Business:** Retail chain with 15 locations
- **Use Case:** Testing multi-business scenarios, shift diversity

---

## 🎯 Testing Scenarios

### Scenario 1: Worker Journey
1. Login as `worker@demo.com`
2. Browse available shifts
3. Apply to shifts matching skills
4. Check calendar and set availability
5. View assigned shifts
6. Check payment history

### Scenario 2: Business Journey
1. Login as `business@demo.com`
2. Post a new shift
3. Review worker applications
4. Assign worker to shift
5. Manage shift completion
6. Process payments via escrow

### Scenario 3: Agency Operations
1. Login as `agency@demo.com`
2. View worker pool (523 workers)
3. Match workers to business needs
4. Place multiple workers at once
5. Track placements and commissions
6. Manage client relationships

### Scenario 4: Admin Oversight
1. Login as `admin@demo.com`
2. Review verification requests
3. Manage user accounts
4. Resolve disputes
5. Monitor compliance
6. View audit logs
7. Configure system settings

### Scenario 5: Cross-Role Interactions
1. Business posts shift → Worker applies → Agency recommends worker → Admin verifies
2. Test full workflow from posting to payment

---

## 🔐 Security Features Testing

### Multi-Factor Authentication (Admin)
- Currently disabled for demo
- To enable: Set `mfa_enabled = true` in users table
- Test MFA setup and verification flows

### Role-Based Access Control
- Test middleware protection:
  - Worker trying to access Business routes → Denied
  - Business trying to access Agency routes → Denied
  - Non-admin accessing Admin panel → Denied

### Profile Completion Requirements
- Test incomplete profiles:
  - Worker without profile → Redirected to profile completion
  - Business without payment method → Redirected to payment setup
  - Agency unverified → Redirected to verification pending

---

## 📊 Dashboard URLs

### Worker Dashboard
```
/worker/dashboard
```

### Business Dashboard
```
/business/dashboard
```

### Agency Dashboard
```
/agency/dashboard
```

### Admin Dashboard
```
/admin/dashboard
```

### Main Dashboard (Auto-redirect based on user type)
```
/dashboard
```

---

## 🧹 Reset Demo Data

To reset all demo users and start fresh:

```bash
# Drop and recreate database
php artisan migrate:fresh

# Re-run demo seeder
php artisan db:seed --class=DemoUsersSeeder
```

---

## 🎨 Enterprise Landing Page Features

The refined landing page now includes:

### ✨ Professional Sections
- **Hero Section:** Enterprise workforce on demand with animated gradients
- **Trust Badges:** ISO 27001, SOC 2, live in 70+ countries
- **Enterprise Stats:** 2.3M+ shifts, 98.7% fill rate, 15min matching
- **Industry Solutions:** Hospitality, Healthcare, Retail, Logistics
- **Live Shift Market:** Real-time shift browsing with AI matching
- **Security & Compliance:** Bank-level security, GDPR, HIPAA
- **How It Works:** 4-step process from post to payout
- **Social Proof:** Trusted by Hilton, Marriott, Amazon, Walmart

### 🎨 Orange Color Palette
- **Primary:** #ff6b35 (Brand Orange)
- **Secondary:** #ff8c42 (Brand Coral)
- **Accent:** #f77f00 (Brand Amber)
- **Dark:** #1a1a2e (Brand Dark)

### 🚀 Performance Features
- Floating animations
- Gradient text effects
- Glass morphism effects
- Smooth hover transitions
- Mobile-first responsive design

---

## 💡 Development Tips

### Quick Login Links
Create a dev toolbar with quick login buttons:
```html
<div class="dev-toolbar">
  <a href="/login?email=worker@demo.com&password=password">Worker</a>
  <a href="/login?email=business@demo.com&password=password">Business</a>
  <a href="/login?email=agency@demo.com&password=password">Agency</a>
  <a href="/login?email=admin@demo.com&password=password">Admin</a>
</div>
```

### Database Inspection
```bash
# View all demo users
php artisan tinker
>>> User::where('email', 'LIKE', '%@demo.com')->get();

# Check worker profiles
>>> WorkerProfile::with('user')->whereHas('user', function($q) {
    $q->where('email', 'LIKE', '%@demo.com');
})->get();
```

---

## 📝 Notes

- All demo users have verified email addresses
- Profiles are marked as complete to skip onboarding
- Payment methods are marked as set up for businesses
- Agency is pre-verified to skip admin approval
- MFA is disabled for admin (enable when implementing MFA feature)
- All users have `active` status

---

## 🐛 Troubleshooting

### Issue: Seeder fails with "Table not found"
**Solution:** Run migrations first
```bash
php artisan migrate
```

### Issue: Demo users already exist
**Solution:** Seeder uses `firstOrCreate`, safe to run multiple times

### Issue: Can't login with demo credentials
**Solution:** Verify database connection and check users table:
```bash
php artisan tinker
>>> User::where('email', 'worker@demo.com')->first();
```

### Issue: Redirected to profile completion
**Solution:** Check profile `is_complete` field:
```bash
php artisan tinker
>>> WorkerProfile::where('user_id', 1)->update(['is_complete' => true]);
```

---

## 🚀 Ready to Test!

Your OVERTIMESTAFF application now has:
- ✅ Enterprise-grade landing page
- ✅ Orange color palette across all components
- ✅ Role-based middleware system
- ✅ Comprehensive demo users for all roles
- ✅ Complete testing scenarios

**Start the application and login with any demo account to explore all dashboards!**
