@component('mail::message')
# ⏰ Shift Reminder

Hi {{ $assignment->worker->name }},

This is a reminder that your shift is coming up:

## {{ $shift->title }}

**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}  
**Location:** {{ $shift->location_address }}, {{ $shift->location_city }}

@if($reminderType === '24hr')
**Your shift starts in 24 hours!**
@else
**Your shift starts in 2 hours!**
@endif

@component('mail::button', ['url' => $url])
View Assignment Details
@endcomponent

**Don't forget to:**
- Plan your route
- Arrive 10 minutes early
- Bring any required items
- Check in when you arrive

See you soon!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
