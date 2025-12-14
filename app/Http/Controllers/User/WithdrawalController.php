<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AdminSettings;
use App\Models\Withdrawals;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AdminWithdrawalPending;

class WithdrawalController extends Controller
{
    use \App\Http\Controllers\Traits\Functions;

    protected $request;
    protected $settings;

    public function __construct(Request $request, AdminSettings $settings)
    {
        $this->request = $request;
        $this->settings = $settings::first();
    }

    /**
     * Display payout method configuration page
     *
     * @return Response
     */
    public function payoutMethod()
    {
        $stripeConnectCountries = explode(',', $this->settings->stripe_connect_countries);

        return view('users.payout_method')->withStripeConnectCountries($stripeConnectCountries);
    }

    /**
     * Configure payout method (PayPal, Payoneer, Zelle, or Bank)
     *
     * @return Response
     */
    public function payoutMethodConfigure()
    {

        if ($this->request->type != 'paypal'
            && $this->request->type != 'bank'
            && $this->request->type != 'payoneer'
            && $this->request->type != 'zelle'
        ) {
            return redirect('settings/payout/method');
        }

        // Validate Email Paypal
        if ($this->request->type == 'paypal') {
            $rules = array(
                'email_paypal' => 'required|email|confirmed',
            );

            $this->validate($this->request, $rules);

            $user                  = User::find(auth()->id());
            $user->paypal_account  = $this->request->email_paypal;
            $user->payment_gateway = 'PayPal';
            $user->save();

            \Session::flash('success', trans('admin.success_update'));
            return redirect('settings/payout/method')->withInput();

        }// Validate Email Paypal

        // Validate Email Payoneer
        elseif ($this->request->type == 'payoneer') {
            $rules = array(
                'email_payoneer' => 'required|email|confirmed',
            );

            $this->validate($this->request, $rules);

            $user                  = User::find(auth()->id());
            $user->payoneer_account  = $this->request->email_payoneer;
            $user->payment_gateway = 'Payoneer';
            $user->save();

            \Session::flash('success', trans('admin.success_update'));
            return redirect('settings/payout/method')->withInput();

        }// Validate Email Payoneer

        // Validate Email Zelle
        elseif ($this->request->type == 'zelle') {
            $rules = array(
                'email_zelle' => 'required|email|confirmed',
            );

            $this->validate($this->request, $rules);

            $user                  = User::find(auth()->id());
            $user->zelle_account  = $this->request->email_zelle;
            $user->payment_gateway = 'Zelle';
            $user->save();

            \Session::flash('success', trans('admin.success_update'));
            return redirect('settings/payout/method')->withInput();

        }// Validate Email Zelle

        elseif ($this->request->type == 'bank') {

            $rules = array(
                'bank_details'  => 'required|min:20',
            );

            $this->validate($this->request, $rules);

            $user                  = User::find(auth()->id());
            $user->bank            = strip_tags($this->request->bank_details);
            $user->payment_gateway = 'Bank';
            $user->save();

            \Session::flash('success', trans('admin.success_update'));
            return redirect('settings/payout/method');
        }

    }

    /**
     * Display list of withdrawal requests
     *
     * @return Response
     */
    public function withdrawals()
    {
        $withdrawals = auth()->user()->withdrawals()->orderBy('id','desc')->paginate(20);

        return view('users.withdrawals')->withWithdrawals($withdrawals);
    }

    /**
     * Create a new withdrawal request
     *
     * @return Response
     */
    public function makeWithdrawals()
    {
        if (auth()->user()->payment_gateway != ''
            && Withdrawals::whereUserId(auth()->id())
            ->whereStatus('pending')
            ->count() == 0) {

            if (auth()->user()->payment_gateway == 'PayPal') {
                $_account = auth()->user()->paypal_account;
            } else {
                $_account = auth()->user()->bank;
            }

            // If custom amount withdrawal
            if ($this->settings->type_withdrawals == 'custom') {

                if ($this->settings->currency_position == 'right') {
                    $currencyPosition =  2;
                } else {
                    $currencyPosition =  null;
                }

                $messages = [
                    'amount.min' => trans('general.amount_minimum'.$currencyPosition, ['symbol' => $this->settings->currency_symbol, 'code' => $this->settings->currency_code]),
                    'amount.max' => trans('general.max_amount_minimum'.$currencyPosition, ['symbol' => $this->settings->currency_symbol, 'code' => $this->settings->currency_code]),
                ];

                $this->request->validate([
                    'amount' => 'required|numeric|min:'.$this->settings->amount_min_withdrawal.'|max:'.auth()->user()->balance,
                ], $messages);

                $amount = $this->request->amount;

            } else {
                $amount = auth()->user()->balance;
            }


            $sql           = new Withdrawals();
            $sql->user_id  = auth()->id();
            $sql->amount   = $amount;
            $sql->gateway  = auth()->user()->payment_gateway;
            $sql->account  = $_account;
            $sql->save();

            // Notify Admin via Email
            try {
                Notification::route('mail' , $this->settings->email_admin)
                    ->notify(new AdminWithdrawalPending($sql));
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }

            // Remove Balance the User
            auth()->user()->decrement('balance', $amount);

        } else {
            return redirect()->back()
                ->withErrors([
                    'errors' => trans('general.withdrawal_pending'),
                ]);
        }

        return redirect('settings/withdrawals');
    }

    /**
     * Delete a pending withdrawal request
     *
     * @return Response
     */
    public function deleteWithdrawal()
    {
        $withdrawal = auth()->user()->withdrawals()
            ->whereId($this->request->id)
            ->whereStatus('pending')
            ->firstOrFail();

        // Add Balance the User again
        auth()->user()->increment('balance', $withdrawal->amount);

        $withdrawal->delete();

        return redirect('settings/withdrawals');

    }
}
