<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\PaymentGateways;

class PaymentCardController extends Controller
{
    protected $request;
    protected $settings;

    public function __construct(Request $request, AdminSettings $settings)
    {
        $this->request = $request;
        $this->settings = $settings::first();
    }

    /**
     * Display form to add/update payment card
     *
     * @return Response
     */
    public function formAddUpdatePaymentCard()
    {
      $payment = PaymentGateways::whereName('Stripe')->whereEnabled(1)->firstOrFail();
      \Stripe\Stripe::setApiKey($payment->key_secret);

      return view('users.add_payment_card', [
        'intent' => auth()->user()->createSetupIntent(),
        'key' => $payment->key
      ]);
    }

    /**
     * Add or update payment card
     *
     * @return Response
     */
    public function addUpdatePaymentCard()
    {

      $payment = PaymentGateways::whereName('Stripe')->whereEnabled(1)->firstOrFail();
      \Stripe\Stripe::setApiKey($payment->key_secret);

      if (! $this->request->payment_method) {
        return response()->json([
          "success" => false
        ]);
      }

      if (! auth()->user()->hasPaymentMethod()) {
          auth()->user()->createOrGetStripeCustomer();
      }

      try {

        auth()->user()->deletePaymentMethods();
      } catch (\Exception $e) {
        // error
      }



      auth()->user()->updateDefaultPaymentMethod($this->request->payment_method);
      auth()->user()->save();

      return response()->json([
        "success" => true
      ]);
    }

    /**
     * Display list of saved payment cards
     *
     * @return Response
     */
   public function myCards()
   {
     $payment = PaymentGateways::whereName('Stripe')->whereEnabled(1)->first();
     $paystackPayment = PaymentGateways::whereName('Paystack')->whereEnabled(1)->first();

     if (! $payment && ! $paystackPayment) {
       abort(404);
     }

     if (auth()->user()->stripe_id != '' && auth()->user()->pm_type != '' && isset($payment->key_secret)) {
       $stripe = new \Stripe\StripeClient($payment->key_secret);

       $response = $stripe->paymentMethods->all([
         'customer' => auth()->user()->stripe_id,
         'type' => 'card',
       ]);

       $expiration = $response->data[0]->card->exp_month.'/'.$response->data[0]->card->exp_year;
     }

     $chargeAmountPaystack = ['NGN' => '50.00', 'GHS' => '0.10', 'ZAR' => '1', 'USD' => 0.20];

     if (array_key_exists($this->settings->currency_code, $chargeAmountPaystack)) {
         $chargeAmountPaystack = $chargeAmountPaystack[$this->settings->currency_code];
     } else {
         $chargeAmountPaystack = 0;
     }

     return view('users.my_cards',[
       'key_secret' => $payment->key_secret ?? null,
       'expiration' => $expiration ?? null,
       'paystackPayment' => $paystackPayment,
       'chargeAmountPaystack' => $chargeAmountPaystack
     ]);
   }

   /**
    * Delete saved payment card
    *
    * @return Response
    */
   public function deletePaymentCard()
   {
     $paymentMethod = auth()->user()->defaultPaymentMethod();

     $paymentMethod->delete();

     return redirect('my/cards')->withSuccessRemoved(__('general.successfully_removed'));
   }
}
