<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Data Request</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: #fff; margin: 0; font-size: 24px;">Verify Your Data Request</h1>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p>Hello,</p>

        <p>We received a {{ $request->type_label }} for the account associated with this email address.</p>

        <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0;"><strong>Request Number:</strong> {{ $request->request_number }}</p>
            <p style="margin: 0 0 10px 0;"><strong>Request Type:</strong> {{ $request->type_label }}</p>
            <p style="margin: 0;"><strong>Submitted:</strong> {{ $request->created_at->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <p>To verify that you made this request, please click the button below:</p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $verificationUrl }}" style="display: inline-block; background: #667eea; color: #fff; text-decoration: none; padding: 14px 28px; border-radius: 6px; font-weight: 600;">Verify My Request</a>
        </div>

        <p style="font-size: 14px; color: #6b7280;">This verification link will expire in 24 hours.</p>

        <p style="font-size: 14px; color: #6b7280;">If you did not make this request, you can safely ignore this email. No changes will be made to your account.</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="font-size: 14px; color: #6b7280;">
            Under GDPR, you have the right to access, correct, delete, or port your personal data. This request will be processed within 30 days of verification in accordance with applicable data protection laws.
        </p>

        <p style="margin: 0; font-size: 14px; color: #6b7280;">
            If you have any questions, please contact our Data Protection Officer at <a href="mailto:{{ config('app.dpo_email', 'privacy@' . parse_url(config('app.url'), PHP_URL_HOST)) }}" style="color: #667eea;">{{ config('app.dpo_email', 'privacy@' . parse_url(config('app.url'), PHP_URL_HOST)) }}</a>
        </p>
    </div>

    <div style="text-align: center; padding: 20px; font-size: 12px; color: #9ca3af;">
        <p style="margin: 0;">{{ config('app.name') }}</p>
        <p style="margin: 5px 0 0 0;">This is an automated message regarding your data privacy request.</p>
    </div>
</body>
</html>
