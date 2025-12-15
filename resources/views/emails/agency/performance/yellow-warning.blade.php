@component('mail::message')
# Performance Warning

Hello {{ $agency->name }},

Your agency's performance for the period **{{ $scorecard->period_start->format('M j') }} - {{ $scorecard->period_end->format('M j, Y') }}** has fallen below our target thresholds. This is an important notification that requires your attention.

## Current Performance Status: <span style="color: #f0ad4e;">YELLOW</span>

---

## Your Metrics vs. Targets

@component('mail::table')
| Metric | Your Score | Target | Status |
|:-------|:----------:|:------:|:------:|
| Fill Rate | {{ number_format($metrics['fill_rate']['actual'], 1) }}% | {{ number_format($metrics['fill_rate']['target'], 1) }}% | {{ $metrics['fill_rate']['actual'] >= $metrics['fill_rate']['target'] ? 'Pass' : 'Below Target' }} |
| No-Show Rate | {{ number_format($metrics['no_show_rate']['actual'], 1) }}% | {{ number_format($metrics['no_show_rate']['target'], 1) }}% | {{ $metrics['no_show_rate']['actual'] <= $metrics['no_show_rate']['target'] ? 'Pass' : 'Above Target' }} |
| Average Rating | {{ number_format($metrics['average_rating']['actual'], 2) }} | {{ number_format($metrics['average_rating']['target'], 2) }} | {{ $metrics['average_rating']['actual'] >= $metrics['average_rating']['target'] ? 'Pass' : 'Below Target' }} |
| Complaint Rate | {{ number_format($metrics['complaint_rate']['actual'], 1) }}% | {{ number_format($metrics['complaint_rate']['target'], 1) }}% | {{ $metrics['complaint_rate']['actual'] <= $metrics['complaint_rate']['target'] ? 'Pass' : 'Above Target' }} |
@endcomponent

---

## Summary Statistics

- **Total Shifts Assigned:** {{ $metrics['total_shifts'] }}
- **Shifts Successfully Filled:** {{ $metrics['shifts_filled'] }}
- **No-Shows:** {{ $metrics['no_shows'] }}
- **Complaints Received:** {{ $metrics['complaints'] }}

@if(isset($metrics['trend']) && $metrics['trend'])
## Trend Analysis

@if($metrics['trend']['fill_rate_change'] > 0)
- Fill Rate: <span style="color: green;">+{{ $metrics['trend']['fill_rate_change'] }}%</span> (improving)
@elseif($metrics['trend']['fill_rate_change'] < 0)
- Fill Rate: <span style="color: red;">{{ $metrics['trend']['fill_rate_change'] }}%</span> (declining)
@endif

@if($metrics['trend']['no_show_rate_change'] < 0)
- No-Show Rate: <span style="color: green;">{{ $metrics['trend']['no_show_rate_change'] }}%</span> (improving)
@elseif($metrics['trend']['no_show_rate_change'] > 0)
- No-Show Rate: <span style="color: red;">+{{ $metrics['trend']['no_show_rate_change'] }}%</span> (increasing)
@endif
@endif

---

## Improvement Deadline

You have **2 weeks** to improve your metrics. Please address the issues outlined above by:

**{{ $deadline->format('l, F j, Y') }}**

---

## Recommended Actions

@foreach($actionPlan['items'] as $action)
- {{ $action }}
@endforeach

---

## What Happens Next?

If your metrics do not improve by the deadline:
- Your status may escalate to **RED** (critical)
- This could result in commission rate increases
- Continued poor performance may lead to account suspension

---

## Acknowledge This Notification

Please acknowledge receipt of this notification within **48 hours** to confirm you have reviewed it.

@component('mail::button', ['url' => $acknowledgeUrl, 'color' => 'primary'])
Acknowledge Notification
@endcomponent

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'secondary'])
View Performance Dashboard
@endcomponent

---

## Need Help?

Our support team is here to help you improve. Contact your account manager or reach out to our support team.

**Support Email:** {{ config('mail.support_address', 'support@overtimestaff.com') }}

Thanks,<br>
{{ config('app.name') }} Performance Team

<small>This notification was generated automatically based on your weekly performance scorecard. Notification ID: {{ $notification->id }}</small>
@endcomponent
