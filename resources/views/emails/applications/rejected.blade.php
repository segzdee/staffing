@component('mail::message')
# Application Update

Hi {{ $application->worker->name }},

Thank you for your interest in the shift:

## {{ $shift->title }}

Unfortunately, we've selected another candidate for this position. Don't worry - there are plenty of other opportunities available!

@component('mail::button', ['url' => $url])
Browse More Shifts
@endcomponent

**Keep applying!** Your next opportunity is just around the corner.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
