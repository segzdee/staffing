@component('mail::message')
# URGENT: Critical Performance Alert

Hello {{ $agency->name }},

**This requires your immediate attention.**

Your agency's performance for the period **{{ $scorecard->period_start->format('M j') }} - {{ $scorecard->period_end->format('M j, Y') }}** is critically below acceptable levels.

## Current Performance Status: <span style="color: #d9534f;">RED - CRITICAL</span>

@if($consecutiveRedWeeks > 1)
@component('mail::panel')
**WARNING:** This is your {{ $consecutiveRedWeeks }}{{ $consecutiveRedWeeks == 2 ? 'nd' : ($consecutiveRedWeeks == 3 ? 'rd' : 'th') }} consecutive week with RED status.
@if($consecutiveRedWeeks >= 2)
- Commission rate increase has been applied or will be applied soon
@endif
@if($consecutiveRedWeeks >= 3)
- **Your account is at risk of suspension**
@endif
@endcomponent
@endif

---

## Your Metrics vs. Targets

@component('mail::table')
| Metric | Your Score | Target | Variance |
|:-------|:----------:|:------:|:--------:|
| Fill Rate | {{ number_format($metrics['fill_rate']['actual'], 1) }}% | {{ number_format($metrics['fill_rate']['target'], 1) }}% | <span style="color: {{ $metrics['fill_rate']['variance'] >= 0 ? 'green' : 'red' }};">{{ $metrics['fill_rate']['variance'] >= 0 ? '+' : '' }}{{ number_format($metrics['fill_rate']['variance'], 1) }}%</span> |
| No-Show Rate | {{ number_format($metrics['no_show_rate']['actual'], 1) }}% | {{ number_format($metrics['no_show_rate']['target'], 1) }}% | <span style="color: {{ $metrics['no_show_rate']['variance'] <= 0 ? 'green' : 'red' }};">{{ $metrics['no_show_rate']['variance'] >= 0 ? '+' : '' }}{{ number_format($metrics['no_show_rate']['variance'], 1) }}%</span> |
| Average Rating | {{ number_format($metrics['average_rating']['actual'], 2) }} | {{ number_format($metrics['average_rating']['target'], 2) }} | <span style="color: {{ $metrics['average_rating']['variance'] >= 0 ? 'green' : 'red' }};">{{ $metrics['average_rating']['variance'] >= 0 ? '+' : '' }}{{ number_format($metrics['average_rating']['variance'], 2) }}</span> |
| Complaint Rate | {{ number_format($metrics['complaint_rate']['actual'], 1) }}% | {{ number_format($metrics['complaint_rate']['target'], 1) }}% | <span style="color: {{ $metrics['complaint_rate']['variance'] <= 0 ? 'green' : 'red' }};">{{ $metrics['complaint_rate']['variance'] >= 0 ? '+' : '' }}{{ number_format($metrics['complaint_rate']['variance'], 1) }}%</span> |
@endcomponent

---

## Critical Issues Identified

@php
$criticalMetrics = [];
if ($metrics['fill_rate']['actual'] < $metrics['fill_rate']['target'] - 10) {
    $criticalMetrics[] = 'Fill rate is critically low (' . number_format($metrics['fill_rate']['actual'], 1) . '%)';
}
if ($metrics['no_show_rate']['actual'] > $metrics['no_show_rate']['target'] + 5) {
    $criticalMetrics[] = 'No-show rate is critically high (' . number_format($metrics['no_show_rate']['actual'], 1) . '%)';
}
if ($metrics['average_rating']['actual'] < 4.0 && $metrics['average_rating']['actual'] > 0) {
    $criticalMetrics[] = 'Worker rating is below acceptable level (' . number_format($metrics['average_rating']['actual'], 2) . ')';
}
if ($metrics['complaint_rate']['actual'] > 5) {
    $criticalMetrics[] = 'Complaint rate is critically high (' . number_format($metrics['complaint_rate']['actual'], 1) . '%)';
}
@endphp

@foreach($criticalMetrics as $issue)
- {{ $issue }}
@endforeach

---

## Improvement Deadline

**You have 1 week to show improvement.**

**Deadline: {{ $deadline->format('l, F j, Y') }}**

---

## Consequences if Not Addressed

@component('mail::panel')
**Failure to improve within the deadline will result in:**

1. **Immediate:** Commission rate increase of 2%
2. **Week 2:** Further commission rate increase (capped at 20%)
3. **Week 3:** Account suspension - you will not be able to accept new shifts
@endcomponent

---

## Required Actions

You must take the following steps immediately:

@foreach($actionPlan['items'] as $index => $action)
{{ $index + 1 }}. {{ $action }}
@endforeach

---

## Action Plan Required

Please submit a detailed action plan within **48 hours** outlining how you will address each critical issue.

@component('mail::button', ['url' => $acknowledgeUrl, 'color' => 'error'])
Acknowledge & Submit Action Plan
@endcomponent

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'secondary'])
View Full Performance Report
@endcomponent

---

## Need Immediate Support?

Given the critical nature of this situation, please reach out immediately:

- **Account Manager:** Contact your dedicated account manager
- **Support:** [{{ config('mail.support_address', 'support@overtimestaff.com') }}](mailto:{{ config('mail.support_address', 'support@overtimestaff.com') }})
- **Emergency Line:** {{ config('app.emergency_support_phone', '1-800-XXX-XXXX') }}

@component('mail::button', ['url' => $supportUrl, 'color' => 'secondary'])
Contact Support
@endcomponent

---

**This is an automated notification. Failure to acknowledge within 48 hours will result in escalation to our administrative team.**

Thanks,<br>
{{ config('app.name') }} Performance Team

<small>Notification ID: {{ $notification->id }} | Priority: CRITICAL</small>
@endcomponent
