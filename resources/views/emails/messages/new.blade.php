@component('mail::message')
# 💬 New Message

Hi {{ $message->recipient->name }},

You have received a new message from **{{ $sender->name }}**:

@component('mail::panel')
{{ Str::limit($message->body, 200) }}
@endcomponent

@component('mail::button', ['url' => $url])
View Conversation
@endcomponent

**Conversation:** {{ $conversation->title ?? 'Direct Message' }}

You can reply directly in the messaging system.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
