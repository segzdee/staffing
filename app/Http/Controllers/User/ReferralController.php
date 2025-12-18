<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\ReferralTransactions;

class ReferralController extends Controller
{
  public Request $request;
  protected $settings;

  public function __construct(Request $request)
  {
    $this->request = $request;
    try {
      $this->settings = \App\Models\AdminSettings::first();
    } catch (\Exception $e) {
      $this->settings = null;
    }
  }

  /**
   * Display user's referral earnings
   *
   * Shows all commission transactions from referred users
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    $transactions = ReferralTransactions::whereReferredBy(auth()->id())
      ->orderBy('id', 'desc')
      ->paginate(20);

    return view('users.referrals', ['transactions' => $transactions]);
  }
}
