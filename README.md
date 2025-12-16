# OvertimeStaff - AI-Powered Shift Marketplace

OvertimeStaff is an advanced shift marketplace platform that connects businesses with qualified workers for on-demand staffing needs. Built with Laravel 8, it features AI-powered matching, instant payouts, and comprehensive shift management tools.

## 🚀 Features

### For Workers
- **Smart Shift Discovery**: AI-powered matching algorithm finds the best shifts based on your skills, location, and availability
- **Instant Applications**: Apply to shifts with one click
- **Calendar Management**: Manage your availability and view all assignments in one place
- **Instant Payouts**: Receive payment 15 minutes after shift completion via Stripe instant transfers
- **Achievement Badges**: Earn badges for reliability, performance, and dedication
- **Skill Verification**: Get your skills and certifications verified for better matches
- **Shift Swapping**: Trade shifts with other workers (pending business approval)

### For Businesses
- **Quick Shift Posting**: Post shifts in minutes with dynamic rate suggestions
- **AI Matching**: Get matched with the most qualified workers automatically
- **Shift Templates**: Create reusable templates for recurring shifts with auto-renewal
- **Bulk Operations**: Generate multiple shifts at once
- **Application Management**: Review, accept, or reject applications with bulk actions
- **Analytics Dashboard**: Track labor costs, fill rates, worker performance, and trends
- **Real-time Updates**: Monitor shift status and worker check-ins in real-time
- **Payment Automation**: Automated escrow and instant payout handling

### For Agencies
- **Worker Management**: Manage a pool of workers and assign them to shifts
- **Multi-business Support**: Handle shifts for multiple client businesses
- **Performance Tracking**: Monitor your workers' performance across all shifts
- **Bulk Assignment**: Assign multiple workers to shifts efficiently

## 🛠 Tech Stack

- **Framework**: Laravel 11.x
- **Database**: MySQL
- **Payment Processing**: Stripe Connect + Stripe Instant Payouts
- **Real-time Features**: Laravel Reverb + Laravel Echo
- **Frontend**: Tailwind CSS, Preline UI, Vite
- **Queue System**: Laravel Horizon

## 📋 Requirements

- PHP >= 8.2
- MySQL >= 8.0
- Composer
- Node.js & NPM
- Stripe Account (for payments)

## ⚙️ Installation

1. **Clone the repository**
```bash
git clone https://github.com/your-org/overtimestaff.git
cd overtimestaff
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment setup**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure your .env file**
```
APP_NAME=OvertimeStaff
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=overtimestaff
DB_USERNAME=root
DB_PASSWORD=

STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
```

5. **Run migrations**
```bash
php artisan migrate
```

6. **Seed the database (optional)**
```bash
php artisan db:seed
```

7. **Build assets**
```bash
npm run dev
```

8. **Start the development server**
```bash
php artisan serve
```

Visit http://localhost:8000

## 🗂 Project Structure

```
app/
├── Http/Controllers/
│   ├── ShiftController.php              # Shift marketplace
│   ├── Business/
│   │   ├── ShiftManagementController.php
│   │   └── AnalyticsController.php
│   ├── Worker/
│   │   ├── ShiftApplicationController.php
│   │   └── CalendarController.php
│   └── Api/
│       └── AgentController.php           # AI Agent API
├── Models/
│   ├── Shift.php
│   ├── ShiftApplication.php
│   ├── ShiftAssignment.php
│   ├── ShiftPayment.php
│   └── ...
└── Services/
    ├── ShiftMatchingService.php          # AI matching algorithm
    ├── ShiftPaymentService.php           # Escrow + instant payouts
    ├── NotificationService.php
    ├── BadgeService.php
    └── AnalyticsService.php
```

## 🔑 Key Concepts

### User Types
- **Worker**: Applies for and completes shifts
- **Business**: Posts shifts and manages workers
- **Agency**: Manages workers on behalf of businesses
- **AI Agent**: Programmatic access via API
- **Admin**: Platform administration

### Shift Lifecycle
1. Business posts shift
2. Workers apply or get AI-matched
3. Business assigns workers
4. Payment held in escrow (Stripe Payment Intent)
5. Workers check in/out
6. Shift completed
7. Payment released after 15 minutes
8. Instant payout to worker (Stripe Transfer)

### Payment Flow
- **Escrow**: Funds captured when worker assigned
- **Platform Fee**: 15% of shift cost
- **Instant Payout**: Workers receive payment 15 minutes after shift completion
- **Dispute Handling**: Payment held if dispute filed

## 🏗 Architecture Overview

### Application Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         User Interface                           │
├─────────────────────────────────────────────────────────────────┤
│  Worker Dashboard   │  Business Dashboard  │  Agency Dashboard  │
│  (Green Theme)      │  (Purple Theme)      │  (Pink Theme)      │
└──────────────┬──────────────┬──────────────┬────────────────────┘
               │              │              │
               ▼              ▼              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Authentication Layer                       │
│  RouteServiceProvider → /dashboard → DashboardController@index   │
│     ├─ isWorker()   → workerDashboard()                         │
│     ├─ isBusiness() → businessDashboard()                       │
│     ├─ isAgency()   → agencyDashboard()                         │
│     └─ isAdmin()    → redirect('/admin')                        │
└──────────────┬──────────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Route Layer (web.php)                       │
├─────────────────────────────────────────────────────────────────┤
│  ✓ Public Routes         (shifts, homepage, auth)               │
│  ✓ Worker Routes         (prefix: /worker, middleware: worker)  │
│  ✓ Business Routes       (prefix: /business, middleware: business)│
│  ✓ Agency Routes         (prefix: /agency, middleware: agency)  │
│  ✓ API Agent Routes      (prefix: /api/agent, middleware: api.agent)│
│  ✓ Admin Routes          (prefix: /admin, middleware: role)     │
└──────────────┬──────────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Controller Layer                            │
├─────────────────────────────────────────────────────────────────┤
│  Core Controllers:                                               │
│  ├─ DashboardController      (Unified routing)                  │
│  ├─ ShiftController          (Shift CRUD)                       │
│  ├─ ShiftSwapController      (Worker trading)                   │
│  ├─ ShiftTemplateController  (Reusable templates)              │
│  ├─ CalendarController       (Availability)                     │
│  └─ OnboardingController     (Multi-type setup)                 │
│                                                                  │
│  Specialized Controllers:                                        │
│  ├─ Business/ShiftManagementController                          │
│  ├─ Worker/ShiftApplicationController                           │
│  └─ Api/AgentController                                         │
└──────────────┬──────────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────────┐
│                 Service Layer (Business Logic)                   │
├─────────────────────────────────────────────────────────────────┤
│  ShiftMatchingService     → AI-powered worker matching          │
│    ├─ Skills match (40%)                                        │
│    ├─ Location proximity (25%)                                  │
│    ├─ Availability (20%)                                        │
│    ├─ Experience (10%)                                          │
│    └─ Rating (5%)                                               │
│                                                                  │
│  ShiftPaymentService      → Escrow + Instant Payouts           │
│    ├─ holdInEscrow()        (Capture funds)                    │
│    ├─ releaseFromEscrow()   (15-min delay)                     │
│    ├─ instantPayout()       (Stripe Transfer)                  │
│    └─ handleDisputes()      (Hold payment)                     │
│                                                                  │
│  NotificationService      → Multi-channel notifications         │
│  BadgeService            → Achievement system                   │
│  AnalyticsService        → Business metrics                     │
│  ShiftSwapService        → Worker trading logic                │
└──────────────┬──────────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Model Layer (Eloquent ORM)                  │
├─────────────────────────────────────────────────────────────────┤
│  Core Models:                                                    │
│  ├─ User (with user_type: worker/business/agency/ai_agent)     │
│  ├─ Shift                                                        │
│  ├─ ShiftApplication                                            │
│  ├─ ShiftAssignment                                             │
│  └─ ShiftPayment                                                │
│                                                                  │
│  Profile Models:                                                │
│  ├─ WorkerProfile                                               │
│  ├─ BusinessProfile                                             │
│  ├─ AgencyProfile                                               │
│  └─ AiAgentProfile                                              │
│                                                                  │
│  Feature Models:                                                │
│  ├─ Skill, WorkerSkill                                          │
│  ├─ Certification, WorkerCertification                          │
│  ├─ Rating, ShiftSwap                                           │
│  ├─ WorkerBadge, ShiftTemplate                                  │
│  └─ ShiftNotification, AvailabilityBroadcast                   │
└──────────────┬──────────────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Database Layer (MySQL)                      │
├─────────────────────────────────────────────────────────────────┤
│  22 Shift Marketplace Tables:                                    │
│  ├─ shifts, shift_applications, shift_assignments              │
│  ├─ shift_payments (escrow tracking)                           │
│  ├─ worker_profiles, business_profiles, agency_profiles        │
│  ├─ skills, certifications, ratings                            │
│  ├─ shift_swaps, shift_templates, worker_badges               │
│  └─ shift_notifications, availability_broadcasts              │
│                                                                  │
│  + Base Laravel tables (users, password_resets, etc.)          │
└─────────────────────────────────────────────────────────────────┘


            ┌──────────────────────────────────────┐
            │      External Integrations           │
            ├──────────────────────────────────────┤
            │  ✓ Stripe Connect                    │
            │  ✓ Stripe Instant Payouts           │
            │  ✓ Laravel Echo (WebSockets)        │
            │  ✓ Pusher/Redis (Real-time)         │
            │  ✓ Email/SMS Notifications          │
            └──────────────────────────────────────┘
```

### Request Flow Example: Worker Applies for Shift

```
1. Worker clicks "Apply" on shift details page
   ↓
2. Route: POST /worker/shifts/{id}/apply
   ↓
3. Middleware: auth, worker (verify user type)
   ↓
4. Controller: Worker\ShiftApplicationController@apply
   ↓
5. Validation: FormRequest validates input
   ↓
6. Service Layer: ShiftMatchingService calculates match score
   ↓
7. Model: ShiftApplication::create() saves to DB
   ↓
8. Service: NotificationService notifies business
   ↓
9. Response: Redirect with success message
   ↓
10. View: Updated applications list shown
```

### Payment Flow Architecture

```
Business Posts Shift
       ↓
Worker Assigned
       ↓
┌──────────────────────────────────┐
│  ShiftPaymentService             │
│  → holdInEscrow()                │
│     ├─ Create Stripe PaymentIntent│
│     ├─ Capture funds from business│
│     └─ Store in shift_payments   │
│        (status: in_escrow)       │
└──────────────────────────────────┘
       ↓
Worker Completes Shift
       ↓
Wait 15 Minutes (dispute window)
       ↓
┌──────────────────────────────────┐
│  ShiftPaymentService             │
│  → releaseFromEscrow()           │
│     ├─ Update status: released   │
│     └─ Trigger instant payout    │
└──────────────────────────────────┘
       ↓
┌──────────────────────────────────┐
│  ShiftPaymentService             │
│  → instantPayout()               │
│     ├─ Get worker Stripe Connect │
│     ├─ Create instant transfer   │
│     ├─ Update shift_payments     │
│     └─ Send notification         │
└──────────────────────────────────┘
       ↓
Worker Receives Funds (15 min total)
```

### Directory Structure (Laravel Best Practices)

```
overtimestaff/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php      ✓ Active
│   │   │   ├── ShiftController.php          ✓ Active
│   │   │   ├── Business/                    ✓ Active
│   │   │   ├── Worker/                      ✓ Active
│   │   │   ├── Api/                         ✓ Active
│   │   │   └── Legacy/                      ⚠ Archived (17 old controllers)
│   │   ├── Middleware/
│   │   │   ├── WorkerMiddleware.php
│   │   │   ├── BusinessMiddleware.php
│   │   │   └── ApiAgentAuth.php
│   │   └── Requests/                        (FormRequest validation)
│   ├── Models/                              (17 Eloquent models)
│   ├── Services/                            (6 service classes)
│   └── Providers/
│       └── RouteServiceProvider.php         (HOME = /dashboard)
├── database/
│   └── migrations/                          (31 total migrations)
│       ├── 2014_* - 2022_*                  (Base Laravel + Permissions)
│       └── 2025_12_15_*                     (22 shift marketplace tables)
├── resources/
│   └── views/
│       ├── dashboard/                       ✓ Active (3 dashboards)
│       ├── shifts/                          ✓ Active
│       ├── worker/                          ✓ Active
│       ├── business/                        ✓ Active
│       ├── auth/                            ✓ Active
│       ├── admin/                           ✓ Active
│       └── legacy/                          ⚠ Archived (59 old views)
├── routes/
│   ├── web.php                              (464 lines, clean structure)
│   └── api.php
└── public/
```

## 📊 Key Features in Detail

### AI Matching Algorithm
Workers are matched to shifts based on:
- Skills match (40%)
- Location proximity (25%)
- Availability (20%)
- Industry experience (10%)
- Rating (5%)

### Dynamic Pricing
Shift rates are automatically adjusted for:
- Urgency level (Critical: +50%, Urgent: +30%)
- Time to shift (Same day: +25%, 2-3 days: +15%)
- Industry (Healthcare: +15%, Professional: +10%)
- Day/Time (Weekend: +10%, Night: +20%)

### Notification System
- Multi-channel: Push, Email, SMS
- 15+ event types
- Scheduled reminders (2 hours, 30 minutes before shift)
- Custom preferences per user

## 🔐 Security

- CSRF protection on all forms
- XSS prevention
- SQL injection protection via Eloquent ORM
- Secure payment handling via Stripe
- Rate limiting on API endpoints
- Two-factor authentication support

## 📱 API Documentation

API endpoints are available for AI agents:

```
POST   /api/agent/shifts          # Create shift
GET    /api/agent/shifts/{id}     # Get shift details
PUT    /api/agent/shifts/{id}     # Update shift
DELETE /api/agent/shifts/{id}     # Cancel shift
GET    /api/agent/workers/search  # Search workers
POST   /api/agent/match/workers   # AI matching
POST   /api/agent/applications/{id}/accept  # Accept application
```

Authentication: API key in header `X-Agent-API-Key`

## 🧪 Testing

```bash
php artisan test
```

## 📝 License

This project is proprietary software. All rights reserved.

## 🤝 Contributing

This is a private project. Contact the development team for contribution guidelines.

## 📧 Support

For support, email support@overtimestaff.com or visit https://overtimestaff.com/help

## 🎯 Roadmap

- [ ] Mobile apps (iOS/Android)
- [ ] Advanced analytics with ML predictions
- [ ] Multi-language support
- [ ] Background check integration
- [ ] Video interview feature
- [ ] Shift recommendations via push notifications
- [ ] Referral program
- [ ] Worker pools/favorites
- [ ] Custom shift types
- [ ] Invoice generation

## 👥 Team

Developed by OvertimeStaff Development Team

---

© 2025 OvertimeStaff, Inc. All rights reserved.
