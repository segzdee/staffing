<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\Notifications;
use App\Models\Products;
use App\Models\Purchases;
use App\Models\ReferralTransactions;
use App\Models\TaxRates;
use App\Models\User;
use App\Models\Withdrawals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    protected $settings;

    public function __construct()
    {
        try {
            $this->settings = \App\Models\AdminSettings::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Save shop settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $messages = [
            'digital_product_sale.required' => __('general.error_type_sale'),
        ];

        $rules = [
            'min_price_product' => 'required|numeric|min:1',
            'max_price_product' => 'required|numeric|min:1',
            'digital_product_sale' => Rule::requiredIf(! $request->custom_content),
        ];

        $this->validate($request, $rules, $messages);

        if ($this->settings) {
            $this->settings->shop = $request->shop;
            $this->settings->min_price_product = $request->min_price_product;
            $this->settings->max_price_product = $request->max_price_product;
            $this->settings->digital_product_sale = $request->digital_product_sale;
            $this->settings->physical_product_sale = $request->physical_product_sale;
            $this->settings->custom_content = $request->custom_content;
            $this->settings->save();
        }

        return back()->withSuccessMessage(trans('admin.success_update'));
    }

    /**
     * Show products
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function products()
    {
        $data = Products::orderBy('id', 'desc')->paginate(20);

        return view('admin.products')->withData($data);
    }

    /**
     * Delete a product
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function productDelete($id)
    {
        $item = Products::findOrFail($id);

        $path = config('path.shop');

        // Delete Notifications
        Notifications::whereType(15)->whereTarget($item->id)->delete();

        // Delete Preview
        foreach ($item->previews as $previews) {
            Storage::delete($path.$previews->name);
        }

        // Delete file
        Storage::delete($path.$item->file);

        // Delete purchases
        $item->purchases()->delete();

        // Delete item
        $item->delete();

        return back();
    }

    /**
     * Show sales
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function sales()
    {
        $sales = Purchases::orderBy('id', 'desc')->paginate(10);

        return view('admin.sales')->withSales($sales);
    }

    /**
     * Refund a sale
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function salesRefund($id)
    {
        $purchase = Purchases::findOrFail($id);

        if ($purchase) {

            $amount = $purchase->transactions()->amount;

            $taxes = TaxRates::whereIn('id', collect(explode('_', $purchase->transactions()->taxes)))->get();
            $totalTaxes = ($amount * $taxes->sum('percentage') / 100);

            // Total paid by buyer
            $amountRefund = number_format($amount + $purchase->transactions()->transaction_fee + $totalTaxes, 2, '.', '');

            // Get amount referral (if exist)
            $referralTransaction = ReferralTransactions::whereTransactionsId($purchase->transactions()->id)->first();

            if ($purchase->transactions()->referred_commission && $referralTransaction) {
                User::find($referralTransaction->referred_by)->decrement('balance', $referralTransaction->earnings);

                // Delete $referralTransaction
                $referralTransaction->delete();
            }

            // Add funds to wallet buyer
            $purchase->user()->increment('wallet', $amountRefund);

            // User Balnce Current
            $userBalance = $purchase->products()->user()->balance;

            // If the creator has withdrawn their entire balance remove from withdrawal
            $withdrawalPending = Withdrawals::whereUserId($purchase->products()->user()->id)->whereStatus('pending')->first();

            // Remove creator funds
            if ($userBalance != 0.00) {
                $purchase->products()->user()->decrement('balance', $purchase->transactions()->earning_net_user);
            } elseif ($withdrawalPending) {
                $withdrawalPending->decrement('amount', $amountRefund);
            } elseif ($userBalance == 0.00 && ! $withdrawalPending) {
                $purchase->products()->user()->decrement('balance', $purchase->transactions()->earning_net_user);
            }

            // Delete transaction
            $purchase->transactions()->delete();

            // Delete purchase
            $purchase->delete();
        }

        return back()->withSuccessMessage(__('general.refund_success'));
    }
}
