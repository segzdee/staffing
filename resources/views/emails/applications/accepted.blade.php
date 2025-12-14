@component('mail::message')
# 🎉 Your Application Was Accepted!

Hi {{ $application->worker->name }},

Great news! Your application has been accepted for:

## {{ $shift->title }}

**Location:** {{ $shift->location_address }}, {{ $shift->location_city }}  
**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}  
**Rate:** ${{ number_format($shift->final_rate, 2) }}/hour

@component('mail::button', ['url' => $url])
View Assignment Details
@endcomponent

**Next Steps:**
1. Review the shift details and location
2. Plan your route and arrival time
3. Check in when you arrive (GPS required)

We'll send you a reminder 24 hours and 2 hours before your shift starts.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
