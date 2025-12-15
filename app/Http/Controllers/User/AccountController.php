<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;

class AccountController extends Controller
{
  public function __construct(
    public Request $request,
    public AdminSettings $settings
  ) {
    $this->settings = $settings::first();
  }

  /**
   * Delete user account permanently
   *
   * Prevents super admin deletion and requires password confirmation
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function destroy()
  {
    // Prevent super admin account deletion
    if (auth()->user()->isSuperAdmin()) {
      return redirect('privacy/security');
    }

    // Verify password before deletion
    if (! \Hash::check($this->request->password, auth()->user()->password)) {
      return back()->with(['incorrect_pass' => trans('general.password_incorrect')]);
    }

    // Delete user and all associated data
    $this->deleteUser(auth()->id());

    return redirect('/');
  }
}
