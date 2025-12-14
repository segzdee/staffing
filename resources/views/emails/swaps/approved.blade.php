@component('mail::message')
# ✅ Shift Swap Approved!

Hi {{ $offeringWorker->name }} and {{ $receivingWorker->name }},

Great news! Your shift swap has been approved:

## {{ $shift->title }}

**Location:** {{ $shift->location_address }}, {{ $shift->location_city }}  
**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}

**Swap Details:**
- **Original Worker:** {{ $offeringWorker->name }}
- **New Worker:** {{ $receivingWorker->name }}

@component('mail::button', ['url' => $url])
View Swap Details
@endcomponent

**Next Steps:**
- {{ $receivingWorker->name }}: You are now assigned to this shift. Please check in when you arrive.
- {{ $offeringWorker->name }}: Your assignment has been transferred. You are no longer responsible for this shift.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
