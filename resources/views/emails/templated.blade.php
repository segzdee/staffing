<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        .header img {
            max-width: 180px;
            height: auto;
        }
        .content {
            margin-bottom: 30px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        .footer a {
            color: #666;
            text-decoration: underline;
        }
        a {
            color: #3b82f6;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            @if(config('app.logo'))
                <img src="{{ config('app.url') }}/{{ config('app.logo') }}" alt="{{ config('app.name') }}">
            @else
                <h1 style="color: #3b82f6; margin: 0;">{{ config('app.name') }}</h1>
            @endif
        </div>

        <div class="content">
            {!! $bodyHtml !!}
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}">Visit our website</a>
                @if(isset($preferences_url))
                    | <a href="{{ $preferences_url }}">Email Preferences</a>
                @endif
                @if(isset($unsubscribe_url))
                    | <a href="{{ $unsubscribe_url }}">Unsubscribe</a>
                @endif
            </p>
        </div>
    </div>

    @if($trackOpens && $logId)
        {{-- Open tracking pixel --}}
        <img src="{{ route('email.track.open', ['id' => $logId]) }}" width="1" height="1" style="display:none;" alt="">
    @endif
</body>
</html>
