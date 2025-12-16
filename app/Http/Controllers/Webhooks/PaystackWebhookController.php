<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PaystackWebhookController
 *
 * Handles incoming webhooks from Paystack payment processing.
 * TODO: Implement full webhook handling for Paystack events.
 */
class PaystackWebhookController extends Controller
{
    /**
     * Handle incoming Paystack webhook.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        Log::info('Paystack webhook received', [
            'event' => $request->input('event'),
        ]);

        // TODO: Implement webhook signature verification
        // TODO: Handle specific event types (charge.success, transfer.success, etc.)

        return response()->json(['status' => 'received']);
    }
}
