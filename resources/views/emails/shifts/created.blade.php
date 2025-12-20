@component('mail::message')
# New Shift Available! 🎯

Hi {{ $worker->name }},

A new shift that matches your profile has been posted:

## {{ $shift->title }}

**Location:** {{ $shift->location_address }}, {{ $shift->location_city }}  
**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}  
**Rate:** @money($shift->final_rate)/hour  
**Industry:** {{ ucfirst($shift->industry) }}

@if($matchScore)
**Your Match Score:** {{ number_format($matchScore, 1) }}% 🎯
@endif

@component('mail::button', ['url' => $url])
View Shift Details
@endcomponent

**Hurry!** This shift may fill up quickly. Apply now to secure your spot.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
