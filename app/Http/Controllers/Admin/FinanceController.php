<?php

namespace App\Http\Controllers\Admin;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\Deposits;
use App\Models\PaymentGateways;
use App\Models\Referrals;
use App\Models\Subscriptions;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Withdrawals;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Mail;
use Yabacon\Paystack;

class FinanceController extends Controller
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
     * Show subscriptions
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function subscriptions()
    {
        $data = Subscriptions::orderBy('id', 'DESC')->paginate(50);

        return view('admin.subscriptions', ['data' => $data]);
    }

    /**
     * Show transactions
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function transactions(Request $request)
    {
        $query = $request->input('q');

        if ($query != '' && strlen($query) > 2) {
            $data = Transactions::where('txn_id', 'LIKE', '%'.$query.'%')->orderBy('id', 'DESC')->paginate(50);
        } else {
            $data = Transactions::orderBy('id', 'DESC')->paginate(50);
        }

        return view('admin.transactions', ['data' => $data]);
    }

    /**
     * Cancel a transaction
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelTransaction($id)
    {
        $transaction = Transactions::whereId($id)->whereApproved('1')->firstOrFail();

        // Cancel subscription
        $subscription = $transaction->subscription();

        switch ($transaction->payment_gateway) {

            case 'Stripe':

                if (isset($subscription)) {
                    $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                    $stripe->subscriptions->cancel($subscription->stripe_id, []);
                }

                break;

            case 'Paystack':

                if (isset($subscription)) {
                    $payment = PaymentGateways::whereId(4)->whereName('Paystack')->whereEnabled(1)->first();

                    $curl = curl_init();

                    curl_setopt_array($curl, [
                        CURLOPT_URL => 'https://api.paystack.co/subscription/'.$id,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'GET',
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer '.$payment->key_secret,
                            'Cache-Control: no-cache',
                        ],
                    ]);

                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);

                    if ($err) {
                        throw new \Exception('cURL Error #:'.$err);
                    } else {
                        $result = json_decode($response);
                    }

                    // initiate the Library's Paystack Object
                    $paystack = new Paystack($payment->key_secret);

                    $paystack->subscription->disable([
                        'code' => $subscription->subscription_id,
                        'token' => $result->data->email_token,
                    ]);
                }

                break;
        }

        if (isset($subscription)) {
            $subscription->delete();
        }

        // Subtract user earnings
        User::whereId($transaction->subscribed)->decrement('balance', $transaction->earning_net_user);

        // Change status transaction to canceled
        $transaction->approved = '2';
        $transaction->earning_net_user = 0;
        $transaction->earning_net_admin = 0;
        $transaction->save();

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/transactions');
    }

    /**
     * Show payment settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function payments()
    {
        $stripeConnectCountries = $this->settings?->stripe_connect_countries ? explode(',', $this->settings->stripe_connect_countries) : [];

        return view('admin.payments-settings')->withStripeConnectCountries($stripeConnectCountries);
    }

    /**
     * Save payment settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function savePayments(Request $request)
    {
        $sql = AdminSettings::first();

        // The referral system cannot be activated if your commission fee equals 0
        if ($request->fee_commission == 0 && $this->settings?->referral_system == 'on') {
            return back()->withErrors([
                'errors' => trans('general.error_fee_commission_zero'),
            ]);
        }

        $messages = [
            'stripe_connect_countries.required' => trans('validation.required', ['attribute' => __('general.stripe_connect_countries')]),
        ];

        $rules = [
            'currency_code' => 'required|alpha',
            'currency_symbol' => 'required',
            'min_subscription_amount' => 'required|numeric|min:1',
            'max_subscription_amount' => 'required|numeric|min:1',
            'stripe_connect_countries' => Rule::requiredIf($request->stripe_connect == 1),
        ];

        $this->validate($request, $rules, $messages);

        if (isset($request->stripe_connect_countries)) {
            $stripeConnectCountries = implode(',', $request->stripe_connect_countries);
        }

        $sql->currency_symbol = $request->currency_symbol;
        $sql->currency_code = strtoupper($request->currency_code);
        $sql->currency_position = $request->currency_position;
        $sql->min_subscription_amount = $request->min_subscription_amount;
        $sql->max_subscription_amount = $request->max_subscription_amount;
        $sql->min_tip_amount = $request->min_tip_amount;
        $sql->max_tip_amount = $request->max_tip_amount;
        $sql->min_ppv_amount = $request->min_ppv_amount;
        $sql->max_ppv_amount = $request->max_ppv_amount;
        $sql->min_deposits_amount = $request->min_deposits_amount;
        $sql->max_deposits_amount = $request->max_deposits_amount;
        $sql->fee_commission = $request->fee_commission;
        $sql->percentage_referred = $request->percentage_referred;
        $sql->referral_transaction_limit = $request->referral_transaction_limit;
        $sql->amount_min_withdrawal = $request->amount_min_withdrawal;
        $sql->days_process_withdrawals = $request->days_process_withdrawals;
        $sql->type_withdrawals = $request->type_withdrawals;
        $sql->payout_method_paypal = $request->payout_method_paypal;
        $sql->payout_method_payoneer = $request->payout_method_payoneer;
        $sql->payout_method_zelle = $request->payout_method_zelle;
        $sql->payout_method_bank = $request->payout_method_bank;
        $sql->decimal_format = $request->decimal_format;
        $sql->disable_wallet = $request->disable_wallet;
        $sql->tax_on_wallet = $request->tax_on_wallet;
        $sql->wallet_format = $request->wallet_format;
        $sql->stripe_connect = $request->stripe_connect;
        $sql->stripe_connect_countries = $stripeConnectCountries ?? null;

        $sql->save();

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/payments');
    }

    /**
     * Show withdrawals
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function withdrawals()
    {
        $data = Withdrawals::orderBy('id', 'DESC')->paginate(50);

        return view('admin.withdrawals', ['data' => $data]);
    }

    /**
     * View a withdrawal
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function withdrawalsView($id)
    {
        $data = Withdrawals::findOrFail($id);

        return view('admin.withdrawal-view', ['data' => $data]);
    }

    /**
     * Mark withdrawal as paid
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function withdrawalsPaid(Request $request)
    {
        $data = Withdrawals::findOrFail($request->id);
        $user = $data->user();
        $data->status = 'paid';
        $data->date_paid = Carbon::now();
        $data->save();

        // Send Email to User
        $amount = Helper::amountWithoutFormat($data->amount).' '.($this->settings?->currency_code ?? 'USD');

        $sender = $this->settings?->email_no_reply ?? config('mail.from.address');
        $titleSite = $this->settings?->title ?? config('app.name');
        $fullNameUser = $user->name;
        $_emailUser = $user->email;

        Mail::send('emails.withdrawal-processed', [
            'amount' => $amount,
            'title_site' => $titleSite,
            'fullname' => $fullNameUser,
        ],
            function ($message) use ($sender, $fullNameUser, $titleSite, $_emailUser) {
                $message->from($sender, $titleSite)
                    ->to($_emailUser, $fullNameUser)
                    ->subject(trans('general.withdrawal_processed').' - '.$titleSite);
            });

        return redirect('panel/admin/withdrawals');
    }

    /**
     * Show payment gateways settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function paymentsGateways($id)
    {
        $data = PaymentGateways::findOrFail($id);
        $name = ucfirst($data->name);

        return view('admin.'.str_slug($name).'-settings')->withData($data);
    }

    /**
     * Save payment gateways settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function savePaymentsGateways($id, Request $request)
    {
        $data = PaymentGateways::findOrFail($id);
        $input = $_POST;

        $this->validate($request, [
            'email' => 'email',
        ]);

        // SECURITY: Only update secret fields if new value provided (not empty)
        $secretFields = ['key_secret', 'webhook_secret', 'ccbill_salt'];
        foreach ($secretFields as $field) {
            if (isset($input[$field]) && empty($input[$field])) {
                unset($input[$field]);
            }
        }

        // Also handle 'key' field for gateways that use it as secret (Mollie)
        if ($data->name == 'Mollie' && isset($input['key']) && empty($input['key'])) {
            unset($input['key']);
        }

        $data->fill($input)->save();

        // SECURITY: Audit log for payment gateway changes
        \Log::channel('admin')->info('Payment gateway settings updated', [
            'admin_id' => auth()->id(),
            'gateway' => $data->name,
            'gateway_id' => $id,
            'changed_fields' => array_keys($input),
            'ip' => $request->ip(),
        ]);

        // Set Keys on .env file (only if new values provided)
        if ($data->name == 'Stripe') {
            if (! empty($input['key'])) {
                Helper::envUpdate('STRIPE_KEY', $input['key']);
            }
            if (! empty($input['key_secret'])) {
                Helper::envUpdate('STRIPE_SECRET', $input['key_secret']);
            }
            if (! empty($input['webhook_secret'])) {
                Helper::envUpdate('STRIPE_WEBHOOK_SECRET', $input['webhook_secret']);
            }
        }

        if ($data->name == 'Flutterwave') {
            if (! empty($input['key'])) {
                Helper::envUpdate('FLW_PUBLIC_KEY', $input['key']);
            }
            if (! empty($input['key_secret'])) {
                Helper::envUpdate('FLW_SECRET_KEY', $input['key_secret']);
            }
        }

        // SECURITY: Audit log for .env updates
        $envUpdates = [];
        foreach ($input as $key => $value) {
            if (! empty($value) && in_array($key, ['key', 'key_secret', 'webhook_secret', 'ccbill_salt'])) {
                $envUpdates[] = $key;
            }
        }
        if (! empty($envUpdates)) {
            \Log::channel('admin')->warning('Environment variables updated via admin panel', [
                'admin_id' => auth()->id(),
                'gateway' => $data->name,
                'updated_keys' => $envUpdates,
                'ip' => $request->ip(),
            ]);
        }

        return back()->withSuccessMessage(__('admin.success_update'));
    }

    /**
     * Show deposits
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function deposits()
    {
        $data = Deposits::orderBy('id', 'desc')->paginate(30);

        return view('admin.deposits')->withData($data);
    }

    /**
     * View a deposit
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function depositsView($id)
    {
        $data = Deposits::findOrFail($id);

        return view('admin.deposits-view')->withData($data);
    }

    /**
     * Approve a deposit
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveDeposits(Request $request)
    {
        $sql = Deposits::findOrFail($request->id);

        // Send Email to User
        $sender = $this->settings?->email_no_reply ?? config('mail.from.address');
        $titleSite = $this->settings?->title ?? config('app.name');
        $fullNameUser = $sql->user()->name;
        $emailUser = $sql->user()->email;

        Mail::send('emails.transfer_verification', [
            'body' => trans('general.info_transfer_verified', ['amount' => Helper::amountFormat($sql->amount)]),
            'type' => 'approve',
            'title_site' => $titleSite,
            'fullname' => $fullNameUser,
        ],
            function ($message) use ($sender, $fullNameUser, $titleSite, $emailUser) {
                $message->from($sender, $titleSite)
                    ->to($emailUser, $fullNameUser)
                    ->subject(trans('general.transfer_verified').' - '.$titleSite);
            });

        $sql->status = 'active';
        $sql->save();

        // Add Funds to User
        User::find($sql->user()->id)->increment('wallet', $sql->amount);

        return redirect('panel/admin/deposits');
    }

    /**
     * Delete a deposit
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteDeposits(Request $request)
    {
        $path = config('path.admin');
        $sql = Deposits::findOrFail($request->id);

        if (isset($sql->user()->name)) {
            // Send Email to User
            $sender = $this->settings?->email_no_reply ?? config('mail.from.address');
            $titleSite = $this->settings?->title ?? config('app.name');
            $fullNameUser = $sql->user()->name;
            $emailUser = $sql->user()->email;

            Mail::send('emails.transfer_verification', [
                'body' => trans('general.info_transfer_not_verified', ['amount' => Helper::amountFormat($sql->amount)]),
                'type' => 'not_approve',
                'title_site' => $titleSite,
                'fullname' => $fullNameUser,
            ],
                function ($message) use ($sender, $fullNameUser, $titleSite, $emailUser) {
                    $message->from($sender, $titleSite)
                        ->to($emailUser, $fullNameUser)
                        ->subject(trans('general.transfer_not_verified').' - '.$titleSite);
                });
        }

        // Delete Image
        \Storage::delete($path.$sql->screenshot_transfer);

        $sql->delete();

        return redirect('panel/admin/deposits');
    }

    /**
     * Show referrals
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function referrals()
    {
        $data = Referrals::orderBy('id', 'desc')->paginate(20);

        return view('admin.referrals')->withData($data);
    }
}
