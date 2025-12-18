<?php

namespace App\Http\Controllers;

use App\Models\EmailPreference;
use Illuminate\Http\Request;

/**
 * COM-003: Email Preferences Controller
 *
 * Allows users to manage their email notification preferences.
 */
class EmailPreferencesController extends Controller
{
    /**
     * Display the user's email preferences.
     */
    public function index()
    {
        $user = auth()->user();
        $preferences = $user->getOrCreateEmailPreferences();

        return view('settings.email-preferences', [
            'preferences' => $preferences,
            'allPreferences' => $preferences->getAllPreferences(),
        ]);
    }

    /**
     * Update the user's email preferences.
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $preferences = $user->getOrCreateEmailPreferences();

        $validated = $request->validate([
            'marketing_emails' => 'boolean',
            'shift_notifications' => 'boolean',
            'payment_notifications' => 'boolean',
            'weekly_digest' => 'boolean',
            'tips_and_updates' => 'boolean',
        ]);

        // Convert checkbox values to boolean
        $preferences->update([
            'marketing_emails' => $request->boolean('marketing_emails'),
            'shift_notifications' => $request->boolean('shift_notifications'),
            'payment_notifications' => $request->boolean('payment_notifications'),
            'weekly_digest' => $request->boolean('weekly_digest'),
            'tips_and_updates' => $request->boolean('tips_and_updates'),
        ]);

        return back()->with('success', 'Email preferences updated successfully.');
    }

    /**
     * Handle unsubscribe via token (public endpoint).
     */
    public function unsubscribe(Request $request, string $token)
    {
        $preferences = EmailPreference::findByToken($token);

        if (! $preferences) {
            return view('emails.unsubscribe-invalid');
        }

        $category = $request->query('category');

        return view('emails.unsubscribe', [
            'preferences' => $preferences,
            'category' => $category,
            'token' => $token,
        ]);
    }

    /**
     * Process unsubscribe request (public endpoint).
     */
    public function processUnsubscribe(Request $request, string $token)
    {
        $preferences = EmailPreference::findByToken($token);

        if (! $preferences) {
            return view('emails.unsubscribe-invalid');
        }

        $category = $request->input('category');

        if ($request->input('unsubscribe_all')) {
            $preferences->unsubscribeFromAll();
            $message = 'You have been unsubscribed from all non-essential emails.';
        } elseif ($category) {
            $preferences->unsubscribeFrom($category);
            $message = 'You have been unsubscribed from this email category.';
        } else {
            return back()->with('error', 'Please select an unsubscribe option.');
        }

        return view('emails.unsubscribe-success', [
            'message' => $message,
            'preferences' => $preferences,
        ]);
    }

    /**
     * Resubscribe to a category (public endpoint).
     */
    public function resubscribe(Request $request, string $token)
    {
        $preferences = EmailPreference::findByToken($token);

        if (! $preferences) {
            return view('emails.unsubscribe-invalid');
        }

        $category = $request->input('category');

        if ($category) {
            $preferences->subscribeTo($category);

            return back()->with('success', 'You have been resubscribed to this email category.');
        }

        return back()->with('error', 'Invalid category.');
    }
}
