<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\PurchaseFeaturedStatusRequest;
use App\Http\Requests\Worker\ReorderPortfolioRequest;
use App\Http\Requests\Worker\UpdatePortfolioItemRequest;
use App\Http\Requests\Worker\UploadPortfolioItemRequest;
use App\Models\WorkerFeaturedStatus;
use App\Models\WorkerPortfolioItem;
use App\Services\WorkerPortfolioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Worker Portfolio Controller
 * WKR-010: Worker Portfolio & Showcase Features
 *
 * Handles portfolio management, public profile settings, and featured status.
 */
class PortfolioController extends Controller
{
    /**
     * Portfolio service instance.
     */
    protected WorkerPortfolioService $portfolioService;

    /**
     * Create a new controller instance.
     */
    public function __construct(WorkerPortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    /**
     * Display the portfolio management page.
     *
     * GET /worker/portfolio
     */
    public function index()
    {
        $worker = auth()->user();
        $portfolioItems = $this->portfolioService->getPortfolioItems($worker);
        $featuredStatus = $this->portfolioService->getActiveFeaturedStatus($worker);
        $analytics = $this->portfolioService->getProfileAnalytics($worker);

        return view('worker.portfolio.index', [
            'portfolioItems' => $portfolioItems,
            'featuredStatus' => $featuredStatus,
            'analytics' => $analytics,
            'maxItems' => WorkerPortfolioItem::MAX_ITEMS_PER_WORKER,
            'canAddMore' => $portfolioItems->count() < WorkerPortfolioItem::MAX_ITEMS_PER_WORKER,
            'publicProfileEnabled' => $worker->workerProfile->public_profile_enabled ?? false,
            'publicProfileSlug' => $worker->workerProfile->public_profile_slug ?? null,
        ]);
    }

    /**
     * Show the upload form.
     *
     * GET /worker/portfolio/upload
     */
    public function create()
    {
        $worker = auth()->user();
        $currentCount = WorkerPortfolioItem::where('worker_id', $worker->id)->count();

        if ($currentCount >= WorkerPortfolioItem::MAX_ITEMS_PER_WORKER) {
            return redirect()->route('worker.portfolio.index')
                ->with('error', 'You have reached the maximum number of portfolio items ('.WorkerPortfolioItem::MAX_ITEMS_PER_WORKER.').');
        }

        return view('worker.portfolio.upload', [
            'types' => WorkerPortfolioItem::TYPES,
            'maxImageSize' => WorkerPortfolioItem::MAX_IMAGE_SIZE / 1024 / 1024, // MB
            'maxVideoSize' => WorkerPortfolioItem::MAX_VIDEO_SIZE / 1024 / 1024, // MB
            'maxDocumentSize' => WorkerPortfolioItem::MAX_DOCUMENT_SIZE / 1024 / 1024, // MB
        ]);
    }

    /**
     * Upload a new portfolio item.
     *
     * POST /worker/portfolio
     */
    public function store(UploadPortfolioItemRequest $request)
    {
        $worker = auth()->user();

        // Rate limiting: 5 uploads per hour
        $key = 'portfolio-upload:'.$worker->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->with('error', 'Too many uploads. Please try again in '.ceil($seconds / 60).' minutes.');
        }

        try {
            $item = $this->portfolioService->uploadItem(
                $worker,
                $request->file('file'),
                $request->input('type'),
                $request->input('title'),
                $request->input('description')
            );

            RateLimiter::hit($key, 3600); // 1 hour

            return redirect()->route('worker.portfolio.index')
                ->with('success', 'Portfolio item uploaded successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show a single portfolio item.
     *
     * GET /worker/portfolio/{portfolioItem}
     */
    public function show(WorkerPortfolioItem $portfolioItem)
    {
        $this->authorizeItem($portfolioItem);

        return view('worker.portfolio.show', [
            'item' => $portfolioItem,
        ]);
    }

    /**
     * Show the edit form for a portfolio item.
     *
     * GET /worker/portfolio/{portfolioItem}/edit
     */
    public function edit(WorkerPortfolioItem $portfolioItem)
    {
        $this->authorizeItem($portfolioItem);

        return view('worker.portfolio.edit', [
            'item' => $portfolioItem,
        ]);
    }

    /**
     * Update a portfolio item.
     *
     * PUT /worker/portfolio/{portfolioItem}
     */
    public function update(UpdatePortfolioItemRequest $request, WorkerPortfolioItem $portfolioItem)
    {
        $this->authorizeItem($portfolioItem);

        try {
            $this->portfolioService->updateItem($portfolioItem, $request->validated());

            return redirect()->route('worker.portfolio.index')
                ->with('success', 'Portfolio item updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a portfolio item.
     *
     * DELETE /worker/portfolio/{portfolioItem}
     */
    public function destroy(WorkerPortfolioItem $portfolioItem)
    {
        $this->authorizeItem($portfolioItem);

        try {
            $this->portfolioService->deleteItem($portfolioItem);

            return redirect()->route('worker.portfolio.index')
                ->with('success', 'Portfolio item deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reorder portfolio items.
     *
     * PUT /worker/portfolio/reorder
     */
    public function reorder(ReorderPortfolioRequest $request)
    {
        $worker = auth()->user();

        try {
            $this->portfolioService->reorderItems($worker, $request->input('items'));

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Set a portfolio item as featured.
     *
     * POST /worker/portfolio/{portfolioItem}/featured
     */
    public function setFeatured(WorkerPortfolioItem $portfolioItem)
    {
        $this->authorizeItem($portfolioItem);

        $worker = auth()->user();

        try {
            $this->portfolioService->setFeaturedItem($worker, $portfolioItem);

            return back()->with('success', 'Featured item updated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove featured status from a portfolio item.
     *
     * DELETE /worker/portfolio/{portfolioItem}/featured
     */
    public function removeFeatured(WorkerPortfolioItem $portfolioItem)
    {
        $this->authorizeItem($portfolioItem);

        try {
            $this->portfolioService->removeFeatured($portfolioItem);

            return back()->with('success', 'Featured status removed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Preview public profile.
     *
     * GET /worker/profile/public-preview
     */
    public function publicPreview()
    {
        $worker = auth()->user();
        $profileData = $this->portfolioService->generatePublicProfile($worker);

        return view('worker.profile.public-preview', [
            'profile' => $profileData,
            'isPreview' => true,
        ]);
    }

    /**
     * Toggle public profile visibility.
     *
     * PUT /worker/profile/visibility
     */
    public function toggleVisibility(Request $request)
    {
        $worker = auth()->user();
        $enable = $request->boolean('enabled');

        try {
            if ($enable) {
                $slug = $this->portfolioService->enablePublicProfile($worker);

                return back()->with('success', 'Public profile enabled. Your profile URL is: '.route('profile.public', $slug));
            } else {
                $this->portfolioService->disablePublicProfile($worker);

                return back()->with('success', 'Public profile disabled.');
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show featured status purchase page.
     *
     * GET /worker/profile/featured
     */
    public function showFeatured()
    {
        $worker = auth()->user();
        $activeFeaturedStatus = $this->portfolioService->getActiveFeaturedStatus($worker);
        $featuredHistory = WorkerFeaturedStatus::where('worker_id', $worker->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('worker.profile.featured', [
            'tiers' => WorkerFeaturedStatus::getTierDetails(),
            'activeFeaturedStatus' => $activeFeaturedStatus,
            'featuredHistory' => $featuredHistory,
        ]);
    }

    /**
     * Purchase featured status.
     *
     * POST /worker/profile/featured
     */
    public function purchaseFeatured(PurchaseFeaturedStatusRequest $request)
    {
        $worker = auth()->user();

        try {
            // Check if worker already has active featured status
            $existing = $this->portfolioService->getActiveFeaturedStatus($worker);
            if ($existing) {
                return back()->with('error', 'You already have an active featured status until '.$existing->end_date->format('M d, Y'));
            }

            $tier = $request->input('tier');
            $tierConfig = WorkerFeaturedStatus::TIERS[$tier] ?? WorkerFeaturedStatus::TIERS['bronze'];
            $amountCents = $tierConfig['cost_cents'];
            $paymentMethod = $request->input('payment_method_id');

            // Process payment using Stripe
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Create or retrieve Stripe customer
            $stripeCustomerId = $worker->stripe_customer_id;

            if (! $stripeCustomerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $worker->email,
                    'name' => $worker->name,
                    'metadata' => [
                        'user_id' => $worker->id,
                        'user_type' => 'worker',
                    ],
                ]);
                $stripeCustomerId = $customer->id;
                $worker->update(['stripe_customer_id' => $stripeCustomerId]);
            }

            // Create a PaymentIntent for the featured status purchase
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'customer' => $stripeCustomerId,
                'payment_method' => $paymentMethod,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'description' => "Featured Status: {$tierConfig['name']} ({$tierConfig['duration_days']} days)",
                'metadata' => [
                    'worker_id' => $worker->id,
                    'tier' => $tier,
                    'product_type' => 'featured_status',
                ],
                'return_url' => route('worker.profile.featured'),
            ]);

            // Check payment status
            if ($paymentIntent->status === 'succeeded') {
                // Payment successful - create and activate featured status
                $featuredStatus = $this->portfolioService->purchaseFeaturedStatus(
                    $worker,
                    $tier,
                    $paymentIntent->id
                );

                $featuredStatus->activate();

                return redirect()->route('worker.profile.featured')
                    ->with('success', 'Featured status activated! Your profile will be boosted for '.$featuredStatus->tier_config['duration_days'].' days.');
            } elseif ($paymentIntent->status === 'requires_action') {
                // 3D Secure or additional authentication required
                return back()->with('requires_action', [
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                    'tier' => $tier,
                ]);
            } else {
                // Payment failed or pending
                return back()->with('error', 'Payment could not be processed. Please try again or use a different payment method.');
            }
        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined
            return back()->with('error', 'Payment declined: '.$e->getError()->message);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Stripe API error
            \Illuminate\Support\Facades\Log::error('Stripe API error during featured status purchase', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Payment processing error. Please try again later.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get portfolio analytics data (AJAX).
     *
     * GET /worker/portfolio/analytics
     */
    public function analytics(Request $request)
    {
        $worker = auth()->user();
        $days = $request->input('days', 30);

        $analytics = $this->portfolioService->getProfileAnalytics($worker, min($days, 90));

        return response()->json($analytics);
    }

    /**
     * Authorize access to a portfolio item.
     */
    protected function authorizeItem(WorkerPortfolioItem $item): void
    {
        if ($item->worker_id !== auth()->id()) {
            abort(403, 'Unauthorized access to portfolio item.');
        }
    }
}
