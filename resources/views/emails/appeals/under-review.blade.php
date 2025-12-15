@component('mail::message')
# Appeal Received

Hi {{ $appeal->worker->name }},

We have received your penalty appeal and it is now **under review**.

---

**Appeal Reference:** #APL-{{ $appeal->id }}

**Submitted:** {{ $appeal->submitted_at ? $appeal->submitted_at->format('F j, Y \a\t g:i A') : 'Just now' }}

**Penalty Details:**
- **Type:** {{ ucfirst(str_replace('_', ' ', $appeal->penalty->penalty_type ?? 'Unknown')) }}
- **Amount:** ${{ number_format($appeal->penalty->penalty_amount ?? 0, 2) }}

---

## What Happens Next

@component('mail::panel')
1. Our team will review your appeal and any evidence provided
2. We may contact you if additional information is needed
3. You will receive a notification once a decision has been made
4. Most appeals are reviewed within **3-5 business days**
@endcomponent

**Important:** While your appeal is under review, the penalty will be placed on hold and will not be deducted from your earnings.

---

You can add additional evidence to your appeal at any time before a decision is made.

@component('mail::button', ['url' => url("/worker/appeals/{$appeal->id}")])
View Appeal Status
@endcomponent

Thank you for your patience.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
