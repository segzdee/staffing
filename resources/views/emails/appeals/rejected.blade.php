@component('mail::message')
# Appeal Decision

Hi {{ $appeal->worker->name }},

We have reviewed your penalty appeal and unfortunately, we were unable to approve it.

---

**Appeal Reference:** #APL-{{ $appeal->id }}

**Penalty Details:**
- **Type:** {{ ucfirst(str_replace('_', ' ', $appeal->penalty->penalty_type ?? 'Unknown')) }}
- **Amount:** ${{ number_format($appeal->penalty->penalty_amount ?? 0, 2) }}
- **Due Date:** {{ $appeal->penalty->due_date ? $appeal->penalty->due_date->format('F j, Y') : 'Not specified' }}

---

**Decision Reason:**

{{ $appeal->decision_reason ?? 'No additional details provided.' }}

---

## What Happens Next

@component('mail::panel')
- The original penalty of **${{ number_format($appeal->penalty->penalty_amount ?? 0, 2) }}** remains in effect
- Payment is due by **{{ $appeal->penalty->due_date ? $appeal->penalty->due_date->format('F j, Y') : 'the specified date' }}**
- If not paid, the amount will be deducted from your next shift payment
@endcomponent

If you believe there has been an error or have additional evidence to support your case, please contact our support team.

@component('mail::button', ['url' => url("/worker/appeals/{$appeal->id}")])
View Appeal Details
@endcomponent

We understand this may not be the outcome you hoped for. We encourage you to review our policies to help avoid future penalties.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
