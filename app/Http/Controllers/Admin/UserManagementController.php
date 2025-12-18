<?php

namespace App\Http\Controllers\Admin;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class UserManagementController extends Controller
{
    protected $settings;

    public function __construct(AdminSettings $settings)
    {
        $this->settings = $settings::first();
    }

    /**
     * Show Members section - OvertimeStaff User Management
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('q');
        $sort = $request->input('sort');

        // Search functionality
        if ($search != '' && strlen($search) > 2) {
            $data = User::where('name', 'LIKE', '%'.$search.'%')
                ->orWhere('username', 'LIKE', '%'.$search.'%')
                ->orWhere('email', 'LIKE', '%'.$search.'%')
                ->orderBy('id', 'desc')->paginate(20);
        } else {
            $data = User::orderBy('id', 'desc')->paginate(20);
        }

        // ==== USER TYPE FILTERS ====
        if (request('sort') == 'workers') {
            $data = User::where('user_type', 'worker')->orderBy('id', 'desc')->paginate(20);
        }

        if (request('sort') == 'businesses') {
            $data = User::where('user_type', 'business')->orderBy('id', 'desc')->paginate(20);
        }

        if (request('sort') == 'agencies') {
            $data = User::where('user_type', 'agency')->orderBy('id', 'desc')->paginate(20);
        }

        // ==== ADMIN FILTER ====
        if (request('sort') == 'admins') {
            $data = User::whereRole('admin')->orderBy('id', 'desc')->paginate(20);
        }

        // ==== VERIFICATION STATUS FILTERS ====
        if (request('sort') == 'verified_workers') {
            $data = User::where('user_type', 'worker')
                ->where('is_verified_worker', true)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (request('sort') == 'verified_businesses') {
            $data = User::where('user_type', 'business')
                ->where('is_verified_business', true)
                ->orderBy('id', 'desc')
                ->paginate(20);
        }

        if (request('sort') == 'pending_verification') {
            $data = User::whereHas('verificationRequest', function ($query) {
                $query->where('status', 'pending');
            })->orderBy('id', 'desc')->paginate(20);
        }

        // ==== ACCOUNT STATUS FILTERS ====
        if (request('sort') == 'email_pending') {
            $data = User::whereStatus('pending')->orderBy('id', 'desc')->paginate(20);
        }

        if (request('sort') == 'suspended') {
            $data = User::whereStatus('suspended')->orderBy('id', 'desc')->paginate(20);
        }

        if (request('sort') == 'active_today') {
            $data = User::where('updated_at', '>=', Carbon::now()->subDay())->orderBy('id', 'desc')->paginate(20);
        }

        return view('admin.members', ['data' => $data, 'query' => $search, 'sort' => $sort]);
    }

    /**
     * Show Users section - alias for index (route compatibility)
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function users(Request $request)
    {
        return $this->index($request);
    }

    /**
     * Edit a user
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        if ($user->id == 1 || $user->id == auth()->user()->id) {
            \Session::flash('info', trans('admin.user_no_edit'));

            return redirect('panel/admin/members');
        }

        return view('admin.edit-member')->withUser($user);
    }

    /**
     * Update a user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($id, Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,'.$id,
        ]);

        $user = User::findOrFail($id);

        if ($request->featured == 'yes' && $user->featured == 'no') {
            $featured_date = Carbon::now();
        } else {
            $featured_date = $user->featured_date;
        }

        if ($request->featured == 'no' && $user->featured == 'yes') {
            $featured_date = null;
        }

        $user->email = $request->email;
        $user->verified_id = $request->verified;
        $user->status = $request->status;
        $user->custom_fee = $request->custom_fee ?? 0;
        $user->featured = $request->featured ?? 'no';
        $user->featured_date = $featured_date;
        $user->wallet = $request->wallet;
        $user->save();

        \Session::flash('success', trans('admin.success_update'));

        return redirect('panel/admin/members');
    }

    /**
     * Delete a user
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id == 1 || $user->id == auth()->user()->id) {
            return redirect('panel/admin/members');
        }

        Helper::deleteUser($id);

        return redirect('panel/admin/members');
    }

    /**
     * Verify a worker (admin action)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyWorker($id)
    {
        $user = User::findOrFail($id);

        if ($user->user_type !== 'worker') {
            return redirect()->back()->with('error', 'User is not a worker.');
        }

        $user->update(['is_verified_worker' => true]);

        if ($user->workerProfile) {
            $user->workerProfile->update(['is_verified' => true]);
        }

        return redirect()->back()->with('success', 'Worker verified successfully.');
    }

    /**
     * Verify a business (admin action)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function verifyBusiness($id)
    {
        $user = User::findOrFail($id);

        if ($user->user_type !== 'business') {
            return redirect()->back()->with('error', 'User is not a business.');
        }

        $user->update(['is_verified_business' => true]);

        if ($user->businessProfile) {
            $user->businessProfile->update(['is_verified' => true]);
        }

        return redirect()->back()->with('success', 'Business verified successfully.');
    }

    /**
     * Resend confirmation email
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resendConfirmationEmail($id)
    {
        $user = User::whereId($id)->whereStatus('pending')->firstOrFail();

        $confirmation_code = Str::random(100);

        $_username = $user->username;
        $_email_user = $user->email;
        $_title_site = $this->settings->title;
        $_email_noreply = $this->settings->email_no_reply;

        Mail::send('emails.verify', ['confirmation_code' => $confirmation_code, 'isProfile' => null],
            function ($message) use (
                $_username,
                $_email_user,
                $_title_site,
                $_email_noreply
            ) {
                $message->from($_email_noreply, $_title_site);
                $message->subject(trans('users.title_email_verify'));
                $message->to($_email_user, $_username);
            });

        $user->update(['confirmation_code' => $confirmation_code]);

        \Session::flash('success', trans('general.send_success'));

        return redirect('panel/admin/members');
    }

    /**
     * Login as user (admin impersonation)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function loginAsUser(Request $request)
    {
        auth()->logout();
        auth()->loginUsingId($request->id);

        return redirect('settings/page');
    }

    /**
     * Show role and permissions page
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function roleAndPermissions($id, Request $request)
    {
        $user = User::findOrFail($id);

        if ($user->id == 1 || $user->id == auth()->user()->id) {
            \Session::flash('info', trans('admin.user_no_edit'));

            return redirect('panel/admin/members');
        }

        $permissions = explode(',', $user->permissions);

        return view('admin.role-and-permissions-member')->with([
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store role and permissions
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeRoleAndPermissions(Request $request)
    {
        if (isset($request->limited_access) && isset($request->permissions)) {
            return back()->withErrorMessage(trans('general.give_access_error'));
        }

        if (! isset($request->limited_access) && isset($request->permissions)) {
            foreach ($request->permissions as $key) {

                if (isset($request->permissions)) {
                    $permissions[] = $key;
                }
            }

            $permissions = implode(',', $permissions);
        } else {
            $permissions = 'limited_access';
        }

        $permission = $request->permission ?: 'none';

        $user = User::findOrFail($request->id);
        $user->role = $request->role;
        $user->permission = $request->role == 'admin' ? $permission : 'none';
        $user->permissions = $request->role == 'admin' ? $permissions : null;
        $user->save();

        return back()->withSuccessMessage(trans('admin.success_update'));
    }
}
