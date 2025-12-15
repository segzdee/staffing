@component('mail::message')
# ⚠️ Shift Cancelled

Hi {{ $recipient->name }},

We're sorry to inform you that the following shift has been cancelled:

## {{ $shift->title }}

**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}

@if($reason)
**Reason:** {{ $reason }}
@endif

@if($recipient->isWorker())
If you were assigned to this shift, any escrow payments will be refunded automatically.

@component('mail::button', ['url' => $url])
Browse Available Shifts
@endcomponent
@else
We apologize for any inconvenience. You can post a new shift at any time.

@component('mail::button', ['url' => route('shifts.create')])
Post New Shift
@endcomponent
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
