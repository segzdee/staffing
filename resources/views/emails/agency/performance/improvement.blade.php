@component('mail::message')
# Congratulations! Your Performance Has Improved

Hello {{ $agency->name }},

Great news! Your agency's performance for the period **{{ $scorecard->period_start->format('M j') }} - {{ $scorecard->period_end->format('M j, Y') }}** has improved.

---

## Performance Status Change

@component('mail::panel')
**Previous Status:** <span style="color: {{ $previousStatus === 'red' ? '#d9534f' : '#f0ad4e' }};">{{ strtoupper($previousStatus) }}</span>

**Current Status:** <span style="color: {{ $currentStatus === 'green' ? '#5cb85c' : '#f0ad4e' }};">{{ strtoupper($currentStatus) }}</span>
@endcomponent

---

## Your Improved Metrics

@component('mail::table')
| Metric | Your Score | Target | Status |
|:-------|:----------:|:------:|:------:|
| Fill Rate | {{ number_format($metrics['fill_rate']['actual'], 1) }}% | {{ number_format($metrics['fill_rate']['target'], 1) }}% | {{ $metrics['fill_rate']['actual'] >= $metrics['fill_rate']['target'] ? 'Passing' : 'Improving' }} |
| No-Show Rate | {{ number_format($metrics['no_show_rate']['actual'], 1) }}% | {{ number_format($metrics['no_show_rate']['target'], 1) }}% | {{ $metrics['no_show_rate']['actual'] <= $metrics['no_show_rate']['target'] ? 'Passing' : 'Improving' }} |
| Average Rating | {{ number_format($metrics['average_rating']['actual'], 2) }} | {{ number_format($metrics['average_rating']['target'], 2) }} | {{ $metrics['average_rating']['actual'] >= $metrics['average_rating']['target'] ? 'Passing' : 'Improving' }} |
| Complaint Rate | {{ number_format($metrics['complaint_rate']['actual'], 1) }}% | {{ number_format($metrics['complaint_rate']['target'], 1) }}% | {{ $metrics['complaint_rate']['actual'] <= $metrics['complaint_rate']['target'] ? 'Passing' : 'Improving' }} |
@endcomponent

---

@if($trend)
## Improvement Trends

Here's how your metrics have changed compared to the previous period:

@if($trend['fill_rate_change'] != 0)
- **Fill Rate:** {{ $trend['fill_rate_change'] >= 0 ? '+' : '' }}{{ number_format($trend['fill_rate_change'], 1) }}% {{ $trend['fill_rate_change'] > 0 ? '(improvement)' : '' }}
@endif

@if($trend['no_show_rate_change'] != 0)
- **No-Show Rate:** {{ $trend['no_show_rate_change'] >= 0 ? '+' : '' }}{{ number_format($trend['no_show_rate_change'], 1) }}% {{ $trend['no_show_rate_change'] < 0 ? '(improvement)' : '' }}
@endif

@if($trend['rating_change'] != 0)
- **Average Rating:** {{ $trend['rating_change'] >= 0 ? '+' : '' }}{{ number_format($trend['rating_change'], 2) }} {{ $trend['rating_change'] > 0 ? '(improvement)' : '' }}
@endif

@if($trend['complaint_rate_change'] != 0)
- **Complaint Rate:** {{ $trend['complaint_rate_change'] >= 0 ? '+' : '' }}{{ number_format($trend['complaint_rate_change'], 1) }}% {{ $trend['complaint_rate_change'] < 0 ? '(improvement)' : '' }}
@endif
@endif

---

## Keep Up the Good Work!

Your improvement shows dedication to quality service. Here are some tips to maintain and continue improving your performance:

@if($currentStatus === 'green')
**You're now in good standing!**

- Continue your current operational practices
- Maintain strong communication with workers
- Keep monitoring your metrics weekly
@else
**Almost there!**

You've made great progress, but there's still room to reach GREEN status:

- Continue focusing on the metrics that are still below target
- Maintain the improvements you've already made
- Keep up your proactive approach to worker management
@endif

---

## Benefits of Maintaining Good Performance

@component('mail::panel')
Agencies with GREEN status enjoy:

- Standard commission rates
- Priority placement in business searches
- Access to premium features
- Recognition as a trusted partner
@endcomponent

---

@component('mail::button', ['url' => $dashboardUrl, 'color' => 'success'])
View Your Performance Dashboard
@endcomponent

---

Thank you for your commitment to improvement and quality service!

Thanks,<br>
{{ config('app.name') }} Performance Team

<small>This is a positive notification recognizing your performance improvement. Notification ID: {{ $notification->id }}</small>
@endcomponent
