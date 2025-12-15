@component('mail::message')
# Account Suspended

Hello {{ $agency->name }},

**Your agency account has been suspended due to sustained poor performance.**

We understand this is a serious matter. This suspension is a result of **{{ $consecutiveRedWeeks }} consecutive weeks** of critical (RED) performance status.

---

## Suspension Details

@component('mail::panel')
**Status:** Account Suspended<br>
**Effective:** Immediately<br>
**Reason:** 3+ consecutive weeks of critical performance issues<br>
**Consecutive Red Weeks:** {{ $consecutiveRedWeeks }}
@endcomponent

---

## What This Means

During suspension, your agency:

- **Cannot accept new shift assignments**
- **Cannot bid on or be matched with new shifts**
- **Will not appear in search results** for businesses
- **Must complete all currently in-progress shifts** professionally

@component('mail::panel')
**Important:** Any currently assigned shifts must still be completed. Failure to complete assigned shifts during suspension will negatively impact your reinstatement process.
@endcomponent

---

## Your Performance History

@component('mail::table')
| Metric | Your Score | Target | Status |
|:-------|:----------:|:------:|:------:|
| Fill Rate | {{ number_format($metrics['fill_rate']['actual'], 1) }}% | {{ number_format($metrics['fill_rate']['target'], 1) }}% | {{ $metrics['fill_rate']['actual'] >= $metrics['fill_rate']['target'] ? 'Pass' : 'Fail' }} |
| No-Show Rate | {{ number_format($metrics['no_show_rate']['actual'], 1) }}% | {{ number_format($metrics['no_show_rate']['target'], 1) }}% | {{ $metrics['no_show_rate']['actual'] <= $metrics['no_show_rate']['target'] ? 'Pass' : 'Fail' }} |
| Average Rating | {{ number_format($metrics['average_rating']['actual'], 2) }} | {{ number_format($metrics['average_rating']['target'], 2) }} | {{ $metrics['average_rating']['actual'] >= $metrics['average_rating']['target'] ? 'Pass' : 'Fail' }} |
| Complaint Rate | {{ number_format($metrics['complaint_rate']['actual'], 1) }}% | {{ number_format($metrics['complaint_rate']['target'], 1) }}% | {{ $metrics['complaint_rate']['actual'] <= $metrics['complaint_rate']['target'] ? 'Pass' : 'Fail' }} |
@endcomponent

---

## Path to Reinstatement

To have your account reinstated, you must complete the following steps:

@component('mail::panel')
### Recovery Requirements

@foreach($recoveryRequirements as $index => $requirement)
{{ $index + 1 }}. {{ $requirement }}
@endforeach
@endcomponent

---

## Submit an Appeal

If you believe this suspension is unwarranted or have evidence of extenuating circumstances, you may submit an appeal.

**Appeal Process:**

1. Submit your appeal through our online form
2. Include detailed explanation and supporting documentation
3. Appeals are reviewed within 5 business days
4. You will receive a decision via email

@component('mail::button', ['url' => $appealUrl, 'color' => 'primary'])
Submit Appeal
@endcomponent

---

## Reinstatement Process

Once you have completed the recovery requirements:

1. Contact your account manager to request a reinstatement review
2. Submit evidence of corrective measures taken
3. Complete a reinstatement interview if requested
4. Upon approval, your account will be reinstated with a **probationary period** of 4 weeks

@component('mail::panel')
**Probationary Period:**
During the probationary period, another RED status week will result in immediate re-suspension with extended review requirements.
@endcomponent

---

## Contact Information

We understand this is a difficult situation. Our team is here to help you through the reinstatement process.

- **Account Manager:** Contact your dedicated account manager
- **Support Email:** {{ config('mail.support_address', 'support@overtimestaff.com') }}
- **Support Phone:** {{ config('app.emergency_support_phone', '1-800-XXX-XXXX') }}

@component('mail::button', ['url' => $supportUrl, 'color' => 'secondary'])
Contact Support
@endcomponent

---

**This suspension will remain in effect until you complete the reinstatement process.**

Thanks,<br>
{{ config('app.name') }} Compliance Team

<small>Notification ID: {{ $notification->id }} | This action was taken in accordance with our platform policies on agency performance standards.</small>
@endcomponent
