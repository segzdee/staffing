@component('mail::message')
# ✅ You've Been Assigned!

Hi {{ $worker->name }},

You've been assigned to a shift:

## {{ $shift->title }}

**Location:** {{ $shift->location_address }}, {{ $shift->location_city }}  
**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}  
**Rate:** @money($shift->final_rate)/hour  
**Duration:** {{ $shift->duration_hours }} hours

@component('mail::button', ['url' => $url])
View Assignment Details
@endcomponent

**Important Reminders:**
- Arrive 10 minutes early
- Check in using the app when you arrive (GPS required)
- Check out when you finish your shift

We'll send you reminders 24 hours and 2 hours before your shift.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
