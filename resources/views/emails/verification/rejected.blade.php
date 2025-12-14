@component('mail::message')
# ⚠️ Account Verification Update

Hi {{ $user->name }},

We've reviewed your verification request, but unfortunately we couldn't approve it at this time.

@if($reason)
**Reason:**
> {{ $reason }}
@else
**Reason:**
> Please review your submitted documents and ensure they meet our verification requirements. You may need to provide additional documentation or update your information.
@endif

**What you can do:**
1. Review the reason above
2. Update your profile information if needed
3. Resubmit your verification request with corrected information

@component('mail::button', ['url' => $url])
Resubmit Verification
@endcomponent

If you have questions or need assistance, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
