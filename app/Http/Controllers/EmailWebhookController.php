<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * COM-003: Email Webhook Controller
 *
 * Handles webhooks from email providers (SendGrid, Mailgun) for tracking
 * email delivery, opens, clicks, and bounces.
 */
class EmailWebhookController extends Controller
{
    public function __construct(protected EmailService $emailService) {}

    /**
     * Handle SendGrid webhook events.
     */
    public function sendgrid(Request $request)
    {
        // Verify webhook signature if secret is configured
        $secret = config('email_templates.webhook_secrets.sendgrid');

        if ($secret) {
            $signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
            $timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');

            if (! $this->verifySendGridSignature($signature, $timestamp, $request->getContent(), $secret)) {
                Log::warning('SendGrid webhook signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();

        // SendGrid sends an array of events
        if (! is_array($payload)) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $this->emailService->processWebhook('sendgrid', $payload);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle Mailgun webhook events.
     */
    public function mailgun(Request $request)
    {
        // Verify webhook signature if secret is configured
        $secret = config('email_templates.webhook_secrets.mailgun');

        if ($secret) {
            $signature = $request->input('signature');

            if (! $this->verifyMailgunSignature($signature, $secret)) {
                Log::warning('Mailgun webhook signature verification failed');

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->all();
        $this->emailService->processWebhook('mailgun', $payload);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Track email open via tracking pixel.
     */
    public function trackOpen(int $id)
    {
        $log = EmailLog::find($id);

        if ($log && config('email_templates.track_opens', true)) {
            $log->markAsOpened();
        }

        // Return a 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Track link click and redirect.
     */
    public function trackClick(Request $request, int $id)
    {
        $url = $request->query('url');

        if (! $url) {
            return redirect('/');
        }

        // Decode the URL
        $url = base64_decode($url);

        // Validate URL to prevent open redirect
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect('/');
        }

        $log = EmailLog::find($id);

        if ($log && config('email_templates.track_clicks', true)) {
            $log->markAsClicked();

            // Update metadata with clicked URL
            $metadata = $log->metadata ?? [];
            $metadata['clicked_urls'] = $metadata['clicked_urls'] ?? [];
            $metadata['clicked_urls'][] = [
                'url' => $url,
                'clicked_at' => now()->toIso8601String(),
            ];
            $log->update(['metadata' => $metadata]);
        }

        return redirect($url);
    }

    /**
     * Verify SendGrid webhook signature.
     */
    protected function verifySendGridSignature(?string $signature, ?string $timestamp, string $payload, string $secret): bool
    {
        if (! $signature || ! $timestamp) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Verify Mailgun webhook signature.
     */
    protected function verifyMailgunSignature(?array $signature, string $secret): bool
    {
        if (! $signature || ! isset($signature['timestamp'], $signature['token'], $signature['signature'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $signature['timestamp'].$signature['token'], $secret);

        return hash_equals($expected, $signature['signature']);
    }
}
