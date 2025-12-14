<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\User;
use App\Models\Restrictions;

class RestrictionController extends Controller
{
  public function __construct(
    public Request $request,
    public AdminSettings $settings
  ) {
    $this->settings = $settings::first();
  }

  /**
   * Display list of restricted users
   *
   * Shows all users that the current user has blocked
   *
   * @return \Illuminate\View\View
   */
  public function index()
  {
    $restrictions = auth()->user()
      ->restrictions()
      ->orderBy('id', 'desc')
      ->paginate(15);

    return view('users.restricted_users')->withRestrictions($restrictions);
  }

  /**
   * Restrict or unrestrict a user (toggle)
   *
   * Prevents self-restriction and admin restriction
   *
   * @param int $id User ID to restrict/unrestrict
   * @return \Illuminate\Http\JsonResponse
   */
  public function toggle($id)
  {
    $verifyUser = User::findOrFail($id);

    // Prevent self-restriction
    if ($verifyUser->id == auth()->id()) {
      abort(500);
    }

    // Prevent admin restriction
    if ($verifyUser->isSuperAdmin()) {
      return response()->json(['success' => true]);
    }

    // Toggle restriction status
    $restrict = Restrictions::firstOrNew([
      'user_id' => auth()->id(),
      'user_restricted' => $id
    ]);

    if ($restrict->exists) {
      $restrict->delete(); // Unrestrict
    } else {
      $restrict->save(); // Restrict
    }

    return response()->json(['success' => true]);
  }
}
