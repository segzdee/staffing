@component('mail::message')
# Notice: Commission Rate Increase

Hello {{ $agency->name }},

Due to sustained poor performance over the past {{ $consecutiveRedWeeks }} consecutive week(s), your commission rate has been increased effective immediately.

---

## Commission Rate Change

@component('mail::panel')
| | Previous Rate | New Rate |
|:--|:------------:|:--------:|
| **Commission Rate** | {{ number_format($previousRate, 2) }}% | **{{ number_format($newRate, 2) }}%** |
@endcomponent

**Effective Date:** {{ now()->format('F j, Y') }}

---

## Why This Happened

Your agency has had **{{ $consecutiveRedWeeks }} consecutive week(s)** of RED (critical) performance status. As outlined in our agency agreement, sustained poor performance results in commission rate adjustments.

### Your Recent Performance

@component('mail::table')
| Metric | Your Score | Target | Status |
|:-------|:----------:|:------:|:------:|
| Fill Rate | {{ number_format($metrics['fill_rate']['actual'], 1) }}% | {{ number_format($metrics['fill_rate']['target'], 1) }}% | {{ $metrics['fill_rate']['actual'] >= $metrics['fill_rate']['target'] ? 'Pass' : 'Fail' }} |
| No-Show Rate | {{ number_format($metrics['no_show_rate']['actual'], 1) }}% | {{ number_format($metrics['no_show_rate']['target'], 1) }}% | {{ $metrics['no_show_rate']['actual'] <= $metrics['no_show_rate']['target'] ? 'Pass' : 'Fail' }} |
| Average Rating | {{ number_format($metrics['average_rating']['actual'], 2) }} | {{ number_format($metrics['average_rating']['target'], 2) }} | {{ $metrics['average_rating']['actual'] >= $metrics['average_rating']['target'] ? 'Pass' : 'Fail' }} |
| Complaint Rate | {{ number_format($metrics['complaint_rate']['actual'], 1) }}% | {{ number_format($metrics['complaint_rate']['target'], 1) }}% | {{ $metrics['complaint_rate']['actual'] <= $metrics['complaint_rate']['target'] ? 'Pass' : 'Fail' }} |
@endcomponent

---

## How to Return to Standard Rates

You can have your commission rate reduced back to standard rates by demonstrating sustained improvement:

1. **Achieve GREEN status** for 4 consecutive weeks
2. **All metrics must meet or exceed targets**
3. **No sanctions or warnings** during the improvement period

@component('mail::panel')
**Rate Reduction Schedule:**
- 4 weeks GREEN status: Rate reduced by 1%
- 8 weeks GREEN status: Return to standard rate
@endcomponent

---

## Recommended Actions

@foreach($actionPlan['items'] as $action)
- {{ $action }}
@endforeach

---

## Important: Continued Poor Performance

@component('mail::panel')
**Warning:** If your performance remains at RED status for one more week (3 consecutive weeks total), your account will be **suspended** and you will not be able to accept new shifts.
@endcomponent

---

## Acknowledge This Notice

Please acknowledge receipt of this notice to confirm you understand the rate change.

@component('mail::button', ['url' => $acknowledgeUrl, 'color' => 'primary'])
Acknowledge Fee Increase
@endcomponent

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'secondary'])
View Performance Dashboard
@endcomponent

@component('mail::button', ['url' => $billingUrl, 'color' => 'secondary'])
View Billing Details
@endcomponent

---

## Questions About This Change?

If you have questions about this commission rate change or need help improving your metrics, please contact our support team.

**Support Email:** {{ config('mail.support_address', 'support@overtimestaff.com') }}

Thanks,<br>
{{ config('app.name') }} Finance & Performance Team

<small>This rate change is applied in accordance with our agency agreement section on performance-based pricing. Notification ID: {{ $notification->id }}</small>
@endcomponent
