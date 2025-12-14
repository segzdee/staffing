@component('mail::message')
# 💰 Payment Released!

Hi {{ $payment->worker->name }},

Your payment has been released for:

## {{ $shift->title }}

**Payment Details:**
- Gross Amount: ${{ number_format($payment->amount_gross, 2) }}
- Platform Fee: ${{ number_format($payment->platform_fee, 2) }}
- **Your Earnings: ${{ number_format($payment->amount_net, 2) }}**

**Transfer Status:** Processing  
**Expected Arrival:** 15-30 minutes

The funds will be transferred to your connected Stripe account shortly.

@component('mail::button', ['url' => $url])
View Earnings Dashboard
@endcomponent

**Note:** If you haven't connected your Stripe account yet, please do so to receive payments.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
