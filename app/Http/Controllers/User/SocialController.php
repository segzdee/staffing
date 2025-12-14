<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\User;
use App\Models\Subscriptions;
use App\Helper;

class SocialController extends Controller
{
  public function __construct(
    public Request $request,
    public AdminSettings $settings
  ) {
    $this->settings = $settings::first();
  }

  /**
   * Search users for @ mentions
   *
   * Returns active users matching the filter query for tagging
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function mentions()
  {
    $users = User::whereStatus('active')
      ->where('username', 'LIKE', '%'.$this->request->filter.'%')
      ->orderBy('verified_id', 'asc')
      ->take(5)
      ->get();

    foreach ($users as $user) {
      $verified = $user->verified_id == 'yes' ? ' <i class="bi bi-patch-check-fill verified"></i>' : null;

      $data[] = [
        'name' => $user->hide_name == 'yes' ? $user->username.$verified : $user->name.$verified,
        'username' => $user->username,
        "avatar" => Helper::getFile(config('path.avatar').$user->avatar)
      ];
    }

    return response()->json([
      'tags' => $data ?? null
    ], 200);
  }

  /**
   * Check if current user is subscribed to another user
   *
   * Returns "subscribed" or "unsubscribed" status
   *
   * @param int $user_id Target user ID to check subscription for
   * @return void (echoes string)
   */
  public function checkSubscription($user_id)
  {
    $buyuser = auth()->user()->id;

    $checkSubscription = Subscriptions::where([
      'user_id' => $buyuser,
      'stripe_price' => "user_".$user_id,
    ])->get();

    if(count($checkSubscription) > 0)
      echo "subscribed";
    else
      echo "unsubscribed";
  }
}
