@component('mail::message')
# ✅ Account Verification Approved!

Hi {{ $user->name }},

Congratulations! Your account verification has been approved.

Your {{ $user->isWorker() ? 'worker' : 'business' }} account is now verified and you have full access to all platform features.

**What this means:**
- ✅ Your profile is now verified
- ✅ You can access all platform features
- ✅ Businesses can see your verified status
- ✅ You can apply for premium shifts

@component('mail::button', ['url' => $url])
Go to Dashboard
@endcomponent

Thank you for your patience during the verification process.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
