<?php

namespace App\Http\Middleware;

use App\Services\ContentModerationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * COM-005: Content Moderation Middleware
 *
 * Automatically moderates message content before it's stored.
 * Can block, flag, or redact content based on moderation rules.
 *
 * Usage:
 * - Apply to message routes: Route::post('/messages', ...)->middleware('moderate.content:message')
 * - The parameter specifies which request field contains the content
 */
class ContentModerationMiddleware
{
    /**
     * The content moderation service.
     */
    protected ContentModerationService $moderationService;

    /**
     * Create a new middleware instance.
     */
    public function __construct(ContentModerationService $moderationService)
    {
        $this->moderationService = $moderationService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  string  $field  The request field containing the content to moderate (default: 'message')
     */
    public function handle(Request $request, Closure $next, string $field = 'message'): Response
    {
        // Skip if disabled in config
        if (! config('moderation.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();

        // Skip for unauthenticated requests
        if (! $user) {
            return $next($request);
        }

        // Skip for admins
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Get the content from the request
        $content = $request->input($field);

        // Skip if no content
        if (empty($content)) {
            return $next($request);
        }

        try {
            // Moderate the content
            $result = $this->moderationService->moderateContent($content, $user);

            // If content is blocked, reject the request
            if (! $result['allowed']) {
                return $this->blockedContentResponse($request, $result);
            }

            // If content was redacted or flagged, update the request with moderated content
            if ($result['action'] !== 'allowed') {
                // Replace the content in the request with the moderated version
                $request->merge([$field => $result['content']]);

                // Store moderation info in request for later logging
                $request->attributes->set('moderation_result', $result);
            }

            // If there are issues, log them (will be processed after message creation)
            if (! empty($result['issues'])) {
                $request->attributes->set('moderation_issues', $result['issues']);
                $request->attributes->set('moderation_action', $result['action']);
                $request->attributes->set('moderation_severity', $result['severity']);
            }

        } catch (\Exception $e) {
            // Log error but allow the request through
            Log::error('Content moderation middleware error', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $next($request);
    }

    /**
     * Return blocked content response.
     */
    protected function blockedContentResponse(Request $request, array $result): Response
    {
        $issueTypes = array_unique(array_column($result['issues'], 'type'));
        $message = $this->getBlockedMessage($issueTypes);

        Log::warning('Content blocked by moderation', [
            'user_id' => $request->user()->id ?? null,
            'ip' => $request->ip(),
            'issue_types' => $issueTypes,
            'severity' => $result['severity'],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'content_blocked',
                'message' => $message,
                'issues' => config('app.debug') ? $result['issues'] : null,
            ], 422);
        }

        return back()
            ->withInput()
            ->with('error', $message);
    }

    /**
     * Get user-friendly blocked message based on issue types.
     */
    protected function getBlockedMessage(array $issueTypes): string
    {
        if (in_array('harassment', $issueTypes)) {
            return 'Your message contains content that violates our harassment policy and cannot be sent.';
        }

        if (in_array('profanity', $issueTypes)) {
            return 'Your message contains inappropriate language and cannot be sent. Please revise and try again.';
        }

        if (in_array('pii', $issueTypes)) {
            return 'Your message contains sensitive personal information that is not allowed. Please remove any SSN, credit card, or bank account numbers.';
        }

        if (in_array('spam', $issueTypes)) {
            return 'Your message has been flagged as potential spam and cannot be sent.';
        }

        return 'Your message could not be sent due to content policy violations. Please review and try again.';
    }
}
