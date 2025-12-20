@component('mail::message')
# 🔄 Shift Swap Request

Hi {{ $receivingWorker->name }},

{{ $offeringWorker->name }} has requested to swap a shift with you:

## {{ $shift->title }}

**Location:** {{ $shift->location_address }}, {{ $shift->location_city }}  
**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}  
**Rate:** @money($shift->final_rate)/hour

@if($swap->reason)
**Reason for swap:**
> {{ $swap->reason }}
@endif

@component('mail::button', ['url' => $url])
Review Swap Request
@endcomponent

**Note:** This swap requires business approval. You can accept or decline the request.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
