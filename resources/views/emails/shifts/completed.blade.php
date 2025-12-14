@component('mail::message')
# ✅ Shift Completed!

Hi {{ $assignment->worker->name }},

Great work! Your shift has been completed:

## {{ $shift->title }}

**Date:** {{ \Carbon\Carbon::parse($shift->shift_date)->format('l, F j, Y') }}  
**Hours Worked:** {{ $assignment->hours_worked ?? $shift->duration_hours }} hours

@if($payment)
**Payment Details:**
- Gross Amount: ${{ number_format($payment->amount_gross, 2) }}
- Platform Fee: ${{ number_format($payment->platform_fee, 2) }}
- **Your Earnings: ${{ number_format($payment->amount_net, 2) }}**

Your payment has been released from escrow and will be transferred to your account within 15-30 minutes.
@endif

@component('mail::button', ['url' => $url])
View Earnings
@endcomponent

**Next Steps:**
1. Rate your experience with {{ $shift->business->name }}
2. Check your earnings dashboard
3. Apply for more shifts!

Thanks for your hard work!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
