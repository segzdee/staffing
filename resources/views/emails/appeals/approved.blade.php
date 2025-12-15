@component('mail::message')
# Appeal Approved

Hi {{ $appeal->worker->name }},

Great news! Your penalty appeal has been **approved**.

---

**Appeal Reference:** #APL-{{ $appeal->id }}

**Penalty Details:**
- **Type:** {{ ucfirst(str_replace('_', ' ', $appeal->penalty->penalty_type ?? 'Unknown')) }}
- **Original Amount:** ${{ number_format($appeal->penalty->penalty_amount ?? 0, 2) }}

---

@if($appeal->adjusted_amount === null || $appeal->adjusted_amount == 0)
## Full Penalty Waiver

The entire penalty has been waived and removed from your account. No payment is required.
@else
## Penalty Reduced

The penalty amount has been reduced to **${{ number_format($appeal->adjusted_amount, 2) }}**.
@endif

---

**Decision Reason:**

{{ $appeal->decision_reason ?? 'No additional details provided.' }}

---

@component('mail::button', ['url' => url("/worker/appeals/{$appeal->id}")])
View Appeal Details
@endcomponent

Thank you for your patience during the review process.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
