@component('mail::message')
# New Application Received! 📋

Hi {{ $shift->business->name }},

You've received a new application for your shift:

## {{ $shift->title }}

**Applicant:** {{ $worker->name }}  
**Applied:** {{ $application->applied_at->format('M j, Y g:i A') }}

@if($application->application_note)
**Note from applicant:**
> {{ $application->application_note }}
@endif

@component('mail::button', ['url' => $url])
Review Application
@endcomponent

You can review this application and other applicants on your shift management page.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
