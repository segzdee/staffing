<x-mail::message>
# Hello {{ $recipientName }}!

**{{ $agencyName }}** has invited you to join their worker pool on OvertimeStaff, the shift marketplace platform.

@if(!empty($personalMessage))
---

**Personal message from {{ $agencyName }}:**

> {{ $personalMessage }}

---
@endif

As a member of {{ $agencyName }}'s team, you'll have access to:

- Curated shift opportunities matched to your skills
- Reliable payment processing
- Support from your agency coordinator

@if(!empty($commissionRate))
**Commission Rate:** {{ $commissionRate }}%
@endif

<x-mail::button :url="$invitationUrl">
View Invitation
</x-mail::button>

This invitation will expire on **{{ $expiryDate }}**.

If you didn't expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
