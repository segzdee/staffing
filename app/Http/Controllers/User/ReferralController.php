<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\ReferralTransactions;

class ReferralController extends Controller
{
  public function __construct(
    public Request $request,
    public AdminSettings $settings
  ) {
    $this->settings = $settings::first();
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
