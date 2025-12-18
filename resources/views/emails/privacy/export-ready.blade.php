<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Data Export is Ready</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 24px;">Your Data Export is Ready</h1>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p>Hello,</p>

        <p>Good news! Your data export request has been completed and is ready for download.</p>

        <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>Request Number:</strong> {{ $request->request_number }}</p>
            <p style="margin: 0 0 10px 0;"><strong>Request Type:</strong> {{ $request->type_label }}</p>
            <p style="margin: 0;"><strong>Completed:</strong> {{ $request->completed_at->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <p>Click the button below to download your data:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $downloadUrl }}" style="display: inline-block; background: #10b981; color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-weight: 600;">Download My Data</a>
        </div>

        <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #92400e;">
                <strong>Important:</strong> This download link will expire in 7 days for security reasons. Please download your data before it expires.
            </p>
        </div>

        <p style="font-size: 14px; color: #6b7280;">
            <strong>What's included in your export:</strong>
        </p>
        <ul style="font-size: 14px; color: #6b7280; padding-left: 20px;">
            <li>Personal information and profile data</li>
            <li>Shift history and work records</li>
            <li>Payment transaction history</li>
            <li>Messages and communications</li>
            <li>Ratings and reviews</li>
            <li>Consent records and preferences</li>
        </ul>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="font-size: 14px; color: #6b7280;">
            Your data is provided in JSON format, which is machine-readable and can be easily imported into other services (data portability).
        </p>

        <p style="margin: 0; font-size: 14px; color: #6b7280;">
            If you have any questions about your data, please contact our Data Protection Officer at <a href="mailto:{{ config('app.dpo_email', 'privacy@' . parse_url(config('app.url'), PHP_URL_HOST)) }}" style="color: #10b981;">{{ config('app.dpo_email', 'privacy@' . parse_url(config('app.url'), PHP_URL_HOST)) }}</a>
        </p>
    </div>

    <div style="text-align: center; padding: 20px; font-size: 12px; color: #9ca3af;">
        <p style="margin: 0;">{{ config('app.name') }}</p>
        <p style="margin: 5px 0 0 0;">This is an automated message regarding your data privacy request.</p>
    </div>
</body>
</html>
