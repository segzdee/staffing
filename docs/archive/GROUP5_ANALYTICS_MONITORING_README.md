# GROUP 5: Analytics & Monitoring Systems

## Overview

This document provides comprehensive documentation for the Analytics & Monitoring infrastructure implemented for OvertimeStaff. This implementation includes business spend analytics, cancellation pattern monitoring, system health monitoring, and compliance reporting.

## Table of Contents

1. [Features Implemented](#features-implemented)
2. [Database Schema](#database-schema)
3. [Services](#services)
4. [Controllers](#controllers)
5. [Jobs & Scheduling](#jobs--scheduling)
6. [Views & Frontend](#views--frontend)
7. [API Endpoints](#api-endpoints)
8. [Configuration](#configuration)
9. [Testing](#testing)
10. [Deployment](#deployment)

---

## Features Implemented

### BIZ-008: Advanced Spend Analytics
- Monthly budget tracking per venue
- 12-week trend analysis with interactive charts
- Cost breakdown by role type
- Venue comparison reports
- Budget alerts at 75%, 90%, and 100% thresholds
- Downloadable reports (PDF, CSV, Excel)
- Real-time budget utilization tracking
- YTD spending analysis

### BIZ-009: Cancellation Pattern Warnings
- Tracks cancellation metrics over rolling 30-day windows
- Escalating warning system:
  - 3 late cancellations = email warning
  - 5 late cancellations = dashboard warning + escrow increase
  - 15% cancellation rate = warning notification
  - 25% cancellation rate = credit suspension
- Automatic cancellation fee calculation
- Comprehensive cancellation history logging
- Business performance metrics integration

### ADM-004: System Health Monitoring
- Real-time metrics dashboard with:
  - API response times (p50, p95, p99 percentiles)
  - Shift fill rate (24-hour rolling)
  - Payment success rate
  - Active user tracking
  - Queue depth monitoring
- Automated incident detection and tracking
- Alert system for critical thresholds
- Incident assignment and resolution workflow
- Historical metric analysis

### ADM-005: Compliance Reporting
- Automated report generation:
  - Daily financial reconciliation
  - Monthly VAT summary
  - Quarterly worker classification
- Complete audit trail for all reports
- Multiple export formats (PDF, CSV)
- Report access logging
- Scheduled automated generation
- Retention and archival system

---

## Database Schema

### New Tables Created

#### `venues`
```php
- id (bigint primary)
- business_profile_id (foreign key)
- name (string)
- code (string, nullable, indexed)
- address, city, state, postal_code, country
- latitude, longitude (decimal, nullable)
- phone, email, contact_person
- monthly_budget, current_month_spend, ytd_spend (integers, in cents)
- total_shifts, completed_shifts, cancelled_shifts (integers)
- fill_rate, average_rating (decimals)
- is_active (boolean)
- timestamps, soft_deletes
```

#### `business_cancellation_logs`
```php
- id (bigint primary)
- business_profile_id, shift_id, cancelled_by_user_id (foreign keys)
- cancellation_type (enum: on_time, late, no_show, emergency)
- cancellation_reason (text)
- hours_before_shift (integer)
- shift_start_time, shift_end_time (timestamps)
- shift_pay_rate, shift_role
- cancellation_fee (integer, in cents)
- fee_waived, fee_waiver_reason
- Pattern tracking fields:
  - total_cancellations_at_time
  - cancellations_last_30_days_at_time
  - cancellation_rate_at_time
- Action tracking:
  - warning_issued, escrow_increased, credit_suspended
  - action_taken_at
- timestamps
```

#### `system_health_metrics`
```php
- id (bigint primary)
- metric_type (enum: api_response_time, shift_fill_rate, payment_success_rate, etc.)
- value (decimal 15,4)
- unit (string: ms, %, count, MB, etc.)
- environment (string: production, staging)
- metadata (json)
- is_healthy (boolean)
- threshold_warning, threshold_critical (decimals)
- recorded_at (timestamp)
- timestamps
```

#### `system_incidents`
```php
- id (bigint primary)
- title, description
- severity (enum: low, medium, high, critical)
- status (enum: open, investigating, resolved, closed)
- triggered_by_metric_id (foreign key)
- affected_service (string)
- detected_at, acknowledged_at, resolved_at (timestamps)
- duration_minutes (integer, calculated)
- assigned_to_user_id (foreign key)
- Impact metrics:
  - affected_users, affected_shifts, failed_payments
- resolution_notes, prevention_steps (text)
- Notification tracking:
  - email_sent, slack_sent
  - last_notification_sent_at
- timestamps
```

#### `compliance_reports`
```php
- id (bigint primary)
- report_type (enum: daily_financial_reconciliation, monthly_vat_summary, etc.)
- period_start, period_end (dates)
- period_label (string: "Q1 2025", "January 2025")
- status (enum: pending, generating, completed, failed)
- error_message (text)
- report_data, summary_stats (json)
- file_path, file_format, file_size
- generated_by_user_id (foreign key)
- generated_at (timestamp)
- generation_time_seconds (integer)
- download_count, last_downloaded_at, last_downloaded_by_user_id
- expires_at (timestamp)
- is_archived (boolean)
- timestamps, soft_deletes
```

#### `compliance_report_access_logs`
```php
- id (bigint primary)
- compliance_report_id, user_id (foreign keys)
- action (enum: view, download, export, email)
- ip_address, user_agent
- metadata (json)
- accessed_at (timestamp)
- timestamps
```

### Modified Tables

#### `business_profiles` (added columns)
```php
// Budget tracking
- monthly_budget, current_month_spend, ytd_spend
- enable_budget_alerts (boolean, default true)
- budget_alert_threshold_75, _90, _100 (integers)
- last_budget_alert_sent_at

// Cancellation tracking
- total_late_cancellations
- late_cancellations_last_30_days
- cancellation_rate (decimal 5,2)
- last_late_cancellation_at

// Escrow/credit adjustments
- requires_increased_escrow (boolean)
- credit_suspended (boolean)
- credit_suspended_at
- credit_suspension_reason (text)
```

#### `shifts` (added column)
```php
- venue_id (foreign key, nullable)
```

---

## Services

### SpendAnalyticsService
**Location:** `/Users/ots/Desktop/Staffing/app/Services/SpendAnalyticsService.php`

**Key Methods:**
- `getBusinessAnalytics($businessProfileId, $timeRange)` - Complete analytics overview
- `getBudgetOverview($businessProfile)` - Current budget status and utilization
- `getSpendByRole($businessProfileId, $timeRange)` - Role-based spending breakdown
- `getTrendAnalysis($businessProfileId, $weeks = 12)` - Weekly spending trends
- `getVenueComparison($businessProfileId, $timeRange)` - Multi-venue comparison
- `getBudgetAlerts($businessProfile)` - Active budget threshold alerts
- `exportToCSV($businessProfileId, $timeRange)` - CSV export functionality

**Usage Example:**
```php
$service = new SpendAnalyticsService();
$analytics = $service->getBusinessAnalytics($businessProfile->id, 'month');
$trendData = $service->getTrendAnalysis($businessProfile->id, 12);
```

### CancellationPatternService
**Location:** `/Users/ots/Desktop/Staffing/app/Services/CancellationPatternService.php`

**Key Methods:**
- `logCancellation($shift, $userId, $type, $reason)` - Record and analyze cancellation
- `getCurrentMetrics($businessProfileId)` - Get current cancellation stats
- `calculateCancellationRate($businessProfileId)` - Calculate rolling 30-day rate
- `getDashboardStats($businessProfileId)` - Dashboard data with warnings
- `getCancellationHistory($businessProfileId, $days)` - Historical cancellation data

**Constants:**
- `LATE_CANCEL_EMAIL_THRESHOLD = 3`
- `LATE_CANCEL_WARNING_THRESHOLD = 5`
- `CANCELLATION_RATE_WARNING = 15.0%`
- `CANCELLATION_RATE_ACTION = 25.0%`
- `ROLLING_WINDOW_DAYS = 30`

**Usage Example:**
```php
$service = new CancellationPatternService();
$log = $service->logCancellation($shift, auth()->id(), 'late', 'Client canceled');
$stats = $service->getDashboardStats($businessProfile->id);
```

### SystemHealthService
**Location:** `/Users/ots/Desktop/Staffing/app/Services/SystemHealthService.php`

**Key Methods:**
- `recordMetric($type, $value, $unit, $metadata)` - Record a health metric
- `getDashboardData()` - Complete health dashboard overview
- `getApiPerformance()` - API response time statistics (p50, p95, p99)
- `getShiftMetrics()` - Shift fill rate and counts
- `getPaymentMetrics()` - Payment success rate statistics
- `getUserActivity()` - Active user counts
- `getQueueStatus()` - Queue depths and failed jobs
- `getInfrastructureMetrics()` - Database, Redis, disk, memory metrics
- `trackApiResponseTime($endpoint, $time)` - Convenient API timing tracker

**Usage Example:**
```php
$service = new SystemHealthService();
$service->recordMetric('api_response_time', 245, 'ms', ['endpoint' => '/api/shifts']);
$dashboard = $service->getDashboardData();
```

### ComplianceReportService
**Location:** `/Users/ots/Desktop/Staffing/app/Services/ComplianceReportService.php`

**Key Methods:**
- `generateDailyReconciliation($date)` - Generate daily financial report
- `generateMonthlyVATReport($month, $year)` - Generate VAT summary
- `generateQuarterlyWorkerClassification($quarter, $year)` - Worker classification report
- `exportToCSV($report)` - Export report to CSV format

**Usage Example:**
```php
$service = new ComplianceReportService();
$report = $service->generateDailyReconciliation('2025-12-14');
$csvPath = $service->exportToCSV($report);
```

---

## Controllers

### Business/AnalyticsController
**Location:** `/Users/ots/Desktop/Staffing/app/Http/Controllers/Business/AnalyticsController.php`

**Routes:**
- `GET /business/analytics` - Main analytics dashboard
- `GET /business/analytics/trend-data` - 12-week trend chart data (AJAX)
- `GET /business/analytics/spend-by-role` - Role spending breakdown (AJAX)
- `GET /business/analytics/venue-comparison` - Venue comparison data (AJAX)
- `GET /business/analytics/budget-alerts` - Current budget alerts (AJAX)
- `GET /business/analytics/cancellation-history` - Cancellation history (AJAX)
- `GET /business/analytics/export-pdf` - Export as PDF
- `GET /business/analytics/export-csv` - Export as CSV
- `GET /business/analytics/export-excel` - Export as Excel

### Admin/SystemHealthController
**Location:** `/Users/ots/Desktop/Staffing/app/Http/Controllers/Admin/SystemHealthController.php`

**Routes:**
- `GET /panel/admin/system-health` - Health monitoring dashboard
- `GET /panel/admin/system-health/realtime-metrics` - Real-time metrics (AJAX, 30s polling)
- `GET /panel/admin/system-health/metric-history/{type}` - Historical metric data
- `GET /panel/admin/system-health/incidents` - All incidents list
- `GET /panel/admin/system-health/incidents/{id}` - Incident detail
- `POST /panel/admin/system-health/incidents/{id}/acknowledge` - Acknowledge incident
- `POST /panel/admin/system-health/incidents/{id}/resolve` - Resolve incident
- `POST /panel/admin/system-health/incidents/{id}/assign` - Assign to user
- `PUT /panel/admin/system-health/incidents/{id}/severity` - Update severity
- `GET /panel/admin/system-health/incident-stats` - Incident statistics
- `POST /panel/admin/system-health/test-alert` - Test alert system

### Admin/ReportsController
**Location:** `/Users/ots/Desktop/Staffing/app/Http/Controllers/Admin/ReportsController.php`

**Routes:**
- `GET /panel/admin/reports` - Compliance reports dashboard
- `GET /panel/admin/reports/{id}` - View specific report
- `GET /panel/admin/reports/{id}/download` - Download report file
- `GET /panel/admin/reports/{id}/export-csv` - Export to CSV
- `POST /panel/admin/reports/generate-daily-reconciliation` - Generate daily report
- `POST /panel/admin/reports/generate-monthly-vat` - Generate monthly VAT
- `POST /panel/admin/reports/generate-quarterly-worker-classification` - Generate quarterly report
- `POST /panel/admin/reports/{id}/archive` - Archive report
- `DELETE /panel/admin/reports/{id}` - Delete report
- `GET /panel/admin/reports/statistics` - Report statistics
- `POST /panel/admin/reports/bulk-generate` - Bulk generation
- `POST /panel/admin/reports/{id}/email` - Email report

---

## Jobs & Scheduling

### MonitorBusinessBudgets
**Location:** `/Users/ots/Desktop/Staffing/app/Jobs/MonitorBusinessBudgets.php`

**Schedule:** Daily at 8:00 AM

**Purpose:** Check all business budgets and send alert notifications when thresholds are reached.

**Logic:**
1. Gets all businesses with budgets and alerts enabled
2. Checks budget utilization for each
3. Sends notifications for threshold breaches (75%, 90%, 100%)
4. Enforces once-per-day alert limit to avoid spam
5. Logs all alert activities

### MonitorBusinessCancellations
**Location:** `/Users/ots/Desktop/Staffing/app/Jobs/MonitorBusinessCancellations.php`

**Schedule:** Daily at 2:00 AM

**Purpose:** Monitor cancellation patterns and flag businesses for review.

**Logic:**
1. Reviews all active businesses
2. Updates rolling 30-day cancellation metrics
3. Identifies businesses exceeding thresholds
4. Logs warnings and required actions
5. Does not auto-suspend but flags for admin review

### GenerateDailyReconciliation
**Location:** `/Users/ots/Desktop/Staffing/app/Jobs/GenerateDailyReconciliation.php`

**Schedule:** Daily at 6:00 AM

**Purpose:** Generate automated daily financial reconciliation report for yesterday.

**Logic:**
1. Collects all payment data for previous day
2. Aggregates by gateway, status, totals
3. Generates PDF report
4. Stores in /storage/compliance-reports/
5. Can notify finance team (optional)

### GenerateMonthlyVATReport
**Location:** `/Users/ots/Desktop/Staffing/app/Jobs/GenerateMonthlyVATReport.php`

**Schedule:** 1st of each month at 7:00 AM

**Purpose:** Generate monthly VAT summary for previous month.

**Logic:**
1. Collects all transactions for previous month
2. Groups by VAT rate
3. Calculates totals and breakdowns
4. Generates PDF with daily breakdown
5. Stores for compliance purposes

### RecordSystemHealthMetrics
**Location:** `/Users/ots/Desktop/Staffing/app/Jobs/RecordSystemHealthMetrics.php`

**Schedule:** Every 5 minutes

**Purpose:** Record system health metrics for monitoring.

**Logic:**
1. Records shift fill rate
2. Records payment success rate
3. Records queue depths for all queues
4. Records infrastructure metrics (DB, Redis, disk, memory)
5. Triggers incidents if thresholds exceeded

### Schedule Configuration
**Location:** `/Users/ots/Desktop/Staffing/app/Console/Kernel.php`

```php
// Monitor business budgets daily at 8 AM
$schedule->job(new MonitorBusinessBudgets())
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

// Monitor business cancellation patterns daily at 2 AM
$schedule->job(new MonitorBusinessCancellations())
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Generate daily financial reconciliation report (6 AM)
$schedule->job(new GenerateDailyReconciliation())
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();

// Generate monthly VAT report (1st of month at 7 AM)
$schedule->job(new GenerateMonthlyVATReport())
    ->monthlyOn(1, '07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Record system health metrics (every 5 minutes)
$schedule->job(new RecordSystemHealthMetrics())
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();
```

---

## Views & Frontend

### Business Analytics Dashboard
**Location:** `/Users/ots/Desktop/Staffing/resources/views/business/analytics/index.blade.php`

**Features:**
- Time range selector (Week/Month/Quarter/Year)
- Budget overview cards with real-time stats
- Budget alert banners (color-coded by severity)
- 12-week spending trend chart (line chart, dual-axis)
- Spend by role pie chart
- Venue comparison bar chart
- Cancellation metrics cards
- Export buttons (PDF/CSV/Excel)

**Charts:** Uses Chart.js 4.4.0 loaded from CDN

**AJAX Endpoints:**
- Charts auto-refresh on time range change
- Real-time data fetching without page reload

### Admin System Health Dashboard
**Location:** `/Users/ots/Desktop/Staffing/resources/views/admin/system-health/index.blade.php`

**Features:**
- Overall health score with status indicator
- Metric cards (open incidents, active users, queue jobs)
- API performance metrics (p50, p95, p99, average)
- Shift metrics (fill rate, totals)
- Payment metrics (success rate, counts)
- Queue status grid
- Infrastructure metrics (DB, Redis, disk)
- Recent incidents list
- Auto-refresh every 30 seconds

### Admin Compliance Reports Dashboard
**Location:** `/Users/ots/Desktop/Staffing/resources/views/admin/reports/index.blade.php`

**Features:**
- Report type filters
- Reports table with status indicators
- Generate report modal with type-specific options
- Download and export actions
- Access tracking
- Report statistics

**Modal Types:**
- Daily Reconciliation: Date picker
- Monthly VAT: Month/Year selectors
- Quarterly Worker Classification: Quarter/Year selectors

---

## API Endpoints

### Business Analytics API

**Get Trend Data**
```
GET /business/analytics/trend-data?weeks=12
Authorization: Bearer {token}

Response:
{
  "labels": ["Dec 4", "Dec 11", ...],
  "datasets": [
    {
      "label": "Weekly Spend ($)",
      "data": [1234.56, 2345.67, ...]
    },
    {
      "label": "Shift Count",
      "data": [12, 15, ...]
    }
  ]
}
```

**Get Spend by Role**
```
GET /business/analytics/spend-by-role?range=month
Authorization: Bearer {token}

Response:
{
  "chart": {
    "labels": ["Server", "Chef", ...],
    "datasets": [...]
  },
  "table": [
    {
      "role": "Server",
      "shift_count": 24,
      "total_spend": 125000,
      "total_spend_dollars": 1250.00,
      "average_cost_dollars": 52.08
    }
  ]
}
```

### System Health API

**Get Realtime Metrics**
```
GET /panel/admin/system-health/realtime-metrics
Authorization: Bearer {token}

Response:
{
  "api_performance": {...},
  "shift_metrics": {...},
  "payment_metrics": {...},
  "overall_health": {...}
}
```

**Acknowledge Incident**
```
POST /panel/admin/system-health/incidents/{id}/acknowledge
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Incident acknowledged",
  "incident": {...}
}
```

### Compliance Reports API

**Generate Daily Reconciliation**
```
POST /panel/admin/reports/generate-daily-reconciliation
Authorization: Bearer {token}
Content-Type: application/json

{
  "date": "2025-12-14"
}

Response:
{
  "success": true,
  "message": "Daily reconciliation report generated successfully",
  "report": {
    "id": 123,
    "status": "completed",
    "file_path": "compliance-reports/123/..."
  }
}
```

---

## Configuration

### Environment Variables

Add to `.env`:
```env
# Budget Monitoring
BUDGET_ALERT_ENABLED=true
BUDGET_CHECK_TIME="08:00"

# Cancellation Monitoring
CANCELLATION_CHECK_TIME="02:00"
LATE_CANCEL_EMAIL_THRESHOLD=3
LATE_CANCEL_WARNING_THRESHOLD=5
CANCELLATION_RATE_WARNING=15.0
CANCELLATION_RATE_ACTION=25.0

# System Health
HEALTH_CHECK_INTERVAL=5
HEALTH_METRICS_RETENTION_DAYS=90

# Compliance Reports
REPORTS_STORAGE_PATH=compliance-reports
REPORTS_RETENTION_DAYS=365
DAILY_RECONCILIATION_TIME="06:00"
MONTHLY_VAT_DAY_OF_MONTH=1
```

### DomPDF Configuration

The system uses DomPDF for PDF generation. Ensure it's installed:

```bash
composer require barryvdh/laravel-dompdf
```

Publish config:
```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

---

## Testing

### Running Migrations

```bash
# Run all new migrations
php artisan migrate

# Or run fresh (WARNING: destroys data)
php artisan migrate:fresh --seed
```

### Testing Jobs Manually

```bash
# Test budget monitoring
php artisan tinker
>>> dispatch(new \App\Jobs\MonitorBusinessBudgets());

# Test cancellation monitoring
>>> dispatch(new \App\Jobs\MonitorBusinessCancellations());

# Test daily reconciliation
>>> dispatch(new \App\Jobs\GenerateDailyReconciliation('2025-12-14'));

# Test system health metrics
>>> dispatch(new \App\Jobs\RecordSystemHealthMetrics());
```

### Testing Services

```php
// Test SpendAnalyticsService
$service = app(\App\Services\SpendAnalyticsService::class);
$analytics = $service->getBusinessAnalytics(1, 'month');
dd($analytics);

// Test CancellationPatternService
$service = app(\App\Services\CancellationPatternService::class);
$stats = $service->getDashboardStats(1);
dd($stats);

// Test SystemHealthService
$service = app(\App\Services\SystemHealthService::class);
$dashboard = $service->getDashboardData();
dd($dashboard);
```

### Seeding Test Data

Create a seeder for test data:

```bash
php artisan make:seeder AnalyticsTestDataSeeder
```

---

## Deployment

### Pre-Deployment Checklist

1. Run migrations on staging:
   ```bash
   php artisan migrate --env=staging
   ```

2. Test all scheduled jobs:
   ```bash
   php artisan schedule:test
   ```

3. Verify queue workers are running:
   ```bash
   php artisan queue:work --tries=3 --timeout=300
   ```

4. Check storage permissions:
   ```bash
   chmod -R 775 storage/app/compliance-reports
   ```

5. Verify cron is configured:
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

### Production Deployment Steps

1. **Backup Database**
   ```bash
   php artisan backup:run
   ```

2. **Put Application in Maintenance Mode**
   ```bash
   php artisan down
   ```

3. **Pull Latest Code**
   ```bash
   git pull origin main
   ```

4. **Install Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run prod
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

6. **Clear Caches**
   ```bash
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. **Restart Queue Workers**
   ```bash
   php artisan queue:restart
   ```

8. **Bring Application Online**
   ```bash
   php artisan up
   ```

### Monitoring After Deployment

1. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. Monitor queue:
   ```bash
   php artisan queue:monitor default,notifications,emails
   ```

3. Verify scheduled jobs are running:
   ```bash
   php artisan schedule:list
   ```

4. Access system health dashboard and verify metrics are recording

---

## Troubleshooting

### Common Issues

**Issue: Budget alerts not sending**
- Check that `enable_budget_alerts` is true in business_profiles
- Verify scheduler is running: `php artisan schedule:work`
- Check last alert time: `business_profiles.last_budget_alert_sent_at`

**Issue: Charts not loading**
- Verify Chart.js CDN is accessible
- Check browser console for JavaScript errors
- Ensure AJAX endpoints return valid JSON

**Issue: Reports failing to generate**
- Check storage permissions: `storage/app/compliance-reports`
- Verify DomPDF is installed
- Check error logs in `compliance_reports.error_message`

**Issue: System health metrics not recording**
- Verify Redis is running: `redis-cli ping`
- Check database connection
- Ensure job is scheduled and running every 5 minutes

### Debug Mode

Enable detailed logging:

```php
// In config/logging.php
'channels' => [
    'analytics' => [
        'driver' => 'daily',
        'path' => storage_path('logs/analytics.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

Use in services:
```php
Log::channel('analytics')->info('Budget check completed', [
    'business_id' => $business->id,
    'utilization' => $utilization
]);
```

---

## Future Enhancements

### Planned Features

1. **Real-time WebSocket Updates**
   - Live dashboard updates without polling
   - Instant incident notifications
   - Real-time budget tracking

2. **Machine Learning Integration**
   - Predictive budget forecasting
   - Anomaly detection in spending patterns
   - Intelligent alert tuning

3. **Advanced Reporting**
   - Custom report builder
   - Scheduled report delivery
   - Multi-format exports (Excel, JSON)

4. **Mobile App Integration**
   - Push notifications for alerts
   - Mobile-optimized dashboards
   - Quick incident response

5. **Enhanced Visualization**
   - Customizable dashboards
   - Drill-down capabilities
   - Comparative analysis tools

---

## Support & Contact

For issues or questions regarding this implementation:

1. Check logs: `storage/logs/laravel.log`
2. Review this documentation
3. Contact development team
4. Open GitHub issue (if applicable)

---

## File Locations Reference

### Migrations
- `/database/migrations/2025_12_15_000001_add_budget_fields_to_business_profiles_table.php`
- `/database/migrations/2025_12_15_000002_create_venues_table.php`
- `/database/migrations/2025_12_15_000003_create_business_cancellation_logs_table.php`
- `/database/migrations/2025_12_15_000004_create_system_health_metrics_table.php`
- `/database/migrations/2025_12_15_000005_create_compliance_reports_table.php`
- `/database/migrations/2025_12_15_000006_add_venue_id_to_shifts_table.php`

### Models
- `/app/Models/Venue.php`
- `/app/Models/BusinessCancellationLog.php`
- `/app/Models/SystemHealthMetric.php`
- `/app/Models/SystemIncident.php`
- `/app/Models/ComplianceReport.php`
- `/app/Models/ComplianceReportAccessLog.php`

### Services
- `/app/Services/SpendAnalyticsService.php`
- `/app/Services/CancellationPatternService.php`
- `/app/Services/SystemHealthService.php`
- `/app/Services/ComplianceReportService.php`

### Controllers
- `/app/Http/Controllers/Business/AnalyticsController.php`
- `/app/Http/Controllers/Admin/SystemHealthController.php`
- `/app/Http/Controllers/Admin/ReportsController.php`

### Jobs
- `/app/Jobs/MonitorBusinessBudgets.php`
- `/app/Jobs/MonitorBusinessCancellations.php`
- `/app/Jobs/GenerateDailyReconciliation.php`
- `/app/Jobs/GenerateMonthlyVATReport.php`
- `/app/Jobs/RecordSystemHealthMetrics.php`

### Notifications
- `/app/Notifications/BudgetAlertNotification.php`
- `/app/Notifications/CancellationWarningNotification.php`

### Views
- `/resources/views/business/analytics/index.blade.php`
- `/resources/views/admin/system-health/index.blade.php`
- `/resources/views/admin/reports/index.blade.php`

### Routes
- `/routes/web.php` (lines 208-217, 393-422)

### Configuration
- `/app/Console/Kernel.php` (lines 160-192)

---

**Last Updated:** 2025-12-15
**Version:** 1.0.0
**Implementation:** Group 5 - Analytics & Monitoring Systems
