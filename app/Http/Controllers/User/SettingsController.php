<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\AdminSettings;
use App\Models\Notifications;
use App\Helper;
use Carbon\Carbon;
use DB;

class SettingsController extends Controller
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
     * Show settings page
     *
     * @return Response
     */
    public function index()
    {
        $user = auth()->user();
        return view('settings.index', compact('user'));
    }

    /**
     * Update profile settings
     *
     * @param Request $request
     * @return Response
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        // Update profile tables based on user type
        if ($user->isWorker() && $user->workerProfile) {
            $user->workerProfile->update([
                'bio' => $request->bio,
                'location' => $request->location,
            ]);
        } elseif ($user->isBusiness() && $user->businessProfile) {
            $user->businessProfile->update([
                'bio' => $request->bio,
                'location' => $request->location,
            ]);
        }

        return redirect()->route('settings.index')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update basic settings
     *
     * @return Response
     */
    public function update()
    {
        $input = $this->request->all();
        $id = auth()->id();

        $validator = Validator::make($input, [
            'profession'  => 'required|min:6|max:100|string',
            'countries_id' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $user               = User::find($id);
        $user->profession   = trim(strip_tags($input['profession']));
        $user->countries_id = trim($input['countries_id']);
        $user->email_new_subscriber = $input['email_new_subscriber'] ?? 'no';
        $user->save();

        \Session::flash('success', trans('auth.success_update'));

        return redirect('settings');
    }

    /**
     * Show notifications page
     *
     * @return Response
     */
    public function notifications()
    {
        // Notifications
        $notifications = DB::table('notifications')
            ->select(DB::raw('
                notifications.id id_noty,
                notifications.type,
                notifications.target,
                notifications.created_at,
                users.id userId,
                users.username,
                users.hide_name,
                users.name,
                users.avatar,
                updates.id,
                updates.description,
                U2.username usernameAuthor,
                messages.message,
                messages.to_user_id userDestination,
                products.name productName
            '))
            ->leftjoin('users', 'users.id', '=', DB::raw('notifications.author'))
            ->leftjoin('updates', 'updates.id', '=', DB::raw('notifications.target'))
            ->leftjoin('messages', 'messages.id', '=', DB::raw('notifications.target'))
            ->leftjoin('users AS U2', 'U2.id', '=', DB::raw('updates.user_id'))
            ->leftjoin('comments', 'comments.updates_id', '=', DB::raw('notifications.target
                AND comments.user_id = users.id
                AND comments.updates_id = updates.id'))
            ->leftjoin('products', 'products.id', '=', DB::raw('notifications.target'))
            ->where('notifications.destination', '=',  auth()->id())
            ->where('users.status', '=',  'active')
            ->groupBy('notifications.id')
            ->orderBy('notifications.id', 'DESC')
            ->paginate(20);

        // Mark seen Notification
        // Note: Legacy notifications table uses 'read' (boolean), not 'status'
        $getNotifications = Notifications::where('destination', auth()->id())->where('read', false);
        $getNotifications->count() > 0 ? $getNotifications->update([
            'read' => true
        ]) : null;

        return view('users.notifications', ['notifications' => $notifications]);
    }

    /**
     * Update notification settings
     *
     * @return Response
     */
    public function updateNotifications()
    {
        $user = User::find(auth()->id());
        $user->notify_new_subscriber = $this->request->notify_new_subscriber ?? 'no';
        $user->notify_liked_post = $this->request->notify_liked_post ?? 'no';
        $user->notify_liked_comment = $this->request->notify_liked_comment ?? 'no';
        $user->notify_commented_post = $this->request->notify_commented_post ?? 'no';
        $user->notify_new_tip = $this->request->notify_new_tip ?? 'no';
        $user->email_new_subscriber = $this->request->email_new_subscriber ?? 'no';
        $user->notify_email_new_post = $this->request->notify_email_new_post ?? 'no';
        $user->notify_new_ppv = $this->request->notify_new_ppv ?? 'no';
        $user->email_new_tip = $this->request->email_new_tip ?? 'no';
        $user->email_new_ppv = $this->request->email_new_ppv ?? 'no';
        $user->notify_live_streaming = $this->request->notify_live_streaming ?? 'no';
        $user->notify_mentions = $this->request->notify_mentions ?? 'no';
        $user->save();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Delete all notifications
     *
     * @return Response
     */
    public function deleteNotifications()
    {
        auth()->user()->notifications()->delete();
        return back();
    }

    /**
     * Show password change page
     *
     * @return Response
     */
    public function password()
    {
        return view('users.password');
    }

    /**
     * Update password
     *
     * @param Request $request
     * @return Response
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if (!\Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->with('error', 'Current password is incorrect.');
        }

        $user->password = \Hash::make($request->new_password);
        $user->save();

        return redirect()->route('settings.index')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Update notification preferences
     *
     * @param Request $request
     * @return Response
     */
    public function updateNotificationPreferences(Request $request)
    {
        $user = auth()->user();

        $user->update([
            'email_notifications' => $request->has('email_notifications') ? 1 : 0,
            'sms_notifications' => $request->has('sms_notifications') ? 1 : 0,
            'push_notifications' => $request->has('push_notifications') ? 1 : 0,
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Notification preferences updated successfully.');
    }

    /**
     * Delete user account
     *
     * @param Request $request
     * @return Response
     */
    public function deleteAccount(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }

        if (!\Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->with('error', 'Password is incorrect.');
        }

        // Soft delete or hard delete based on requirements
        $user->delete();

        auth()->logout();

        return redirect()->route('home')
            ->with('success', 'Your account has been deleted.');
    }

    /**
     * Show edit profile page
     *
     * @return Response
     */
    public function editPage()
    {
        $genders = explode(',', $this->settings->genders);
        $categories = explode(',', auth()->user()->categories_id);

        return view('users.edit_my_page', [
            'genders' => $genders,
            'categories' => $categories
        ]);
    }

    /**
     * Update profile page
     *
     * @return Response
     */
    public function updatePage()
    {
        $input = $this->request->all();
        $id    = auth()->id();
        $input['is_admin'] = auth()->user()->permissions;
        $input['is_creator'] = auth()->user()->verified_id == 'yes' ? 0 : 1;
        $input['is_birthdateChanged'] = auth()->user()->birthdate_changed == 'no' ? 0 : 1;

        $messages = array (
            "letters" => trans('validation.letters'),
            "email.required_if" => trans('validation.required'),
            "birthdate.before" => trans('general.error_adult'),
            "birthdate.required_if" => trans('validation.required'),
            "story.required_if" => trans('validation.required'),
        );

        Validator::extend('ascii_only', function($attribute, $value, $parameters){
            return !preg_match('/[^x00-x7F\-]/i', $value);
        });

        // Validate if have one letter
        Validator::extend('letters', function($attribute, $value, $parameters){
            return preg_match('/[a-zA-Z0-9]/', $value);
        });

        $validator = Validator::make($input, [
            'full_name' => 'required|string|max:100',
            'username'  => 'required|min:3|max:25|ascii_only|alpha_dash|letters|unique:pages,slug|unique:reserved,name|unique:users,username,'.$id,
            'email'  => 'required_if:is_admin,==,full_access|unique:users,email,'.$id,
            'website' => 'url',
            'facebook' => 'url',
            'twitter' => 'url',
            'instagram' => 'url',
            'youtube' => 'url',
            'pinterest' => 'url',
            'github' => 'url',
            'snapchat' => 'url',
            'tiktok' => 'url',
            'telegram' => 'url',
            'twitch' => 'url',
            'discord' => 'url',
            'vk' => 'url',
            'story' => 'required_if:is_creator,==,0|max:'.$this->settings->story_length.'',
            'countries_id' => 'required',
            'city' => 'max:100',
            'address' => 'max:100',
            'zip' => 'max:20',
            'profession'  => 'min:6|max:100|string',
            'birthdate' => 'required_if:is_birthdateChanged,==,0|date_format:'.Helper::formatDatepicker().'|before:'.Carbon::now()->subYears(18),
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->getMessageBag()->toArray(),
            ]);
        }

        $story = $this->request->story ?: auth()->user()->story;

        $categories = $this->request->categories_id ? implode( ',', $this->request->categories_id) : '';

        $user                  = User::find($id);
        $user->name            = strip_tags($this->request->full_name);
        $user->username        = trim($this->request->username);
        $user->email           = $this->request->email ? trim($this->request->email) : auth()->user()->email;
        $user->website         = trim($this->request->website) ?? '';
        $user->categories_id   = $categories;
        $user->profession      = $this->request->profession;
        $user->countries_id    = $this->request->countries_id;
        $user->city            = $this->request->city;
        $user->address         = $this->request->address;
        $user->zip             = $this->request->zip;
        $user->company         = $this->request->company;
        $user->story           = trim(Helper::checkTextDb($story));
        $user->facebook        = trim($this->request->facebook) ?? '';
        $user->twitter         = trim($this->request->twitter) ?? '';
        $user->instagram       = trim($this->request->instagram) ?? '';
        $user->youtube         = trim($this->request->youtube) ?? '';
        $user->pinterest       = trim($this->request->pinterest) ?? '';
        $user->github          = trim($this->request->github) ?? '';
        $user->snapchat        = trim($this->request->snapchat) ?? '';
        $user->tiktok          = trim($this->request->tiktok) ?? '';
        $user->telegram        = trim($this->request->telegram) ?? '';
        $user->twitch          = trim($this->request->twitch) ?? '';
        $user->discord         = trim($this->request->discord) ?? '';
        $user->vk              = trim($this->request->vk) ?? '';
        $user->plan            = 'user_'.auth()->id();
        $user->gender          = $this->request->gender;
        $user->birthdate       = auth()->user()->birthdate_changed == 'no' ? Carbon::createFromFormat(Helper::formatDatepicker(), $this->request->birthdate)->format('m/d/Y') : auth()->user()->birthdate;
        $user->birthdate_changed = 'yes';
        $user->language      = $this->request->language;
        $user->hide_name     = $this->request->hide_name ?? 'no';
        $user->save();

        return response()->json([
            'success' => true,
            'url' => url(trim($this->request->username)),
            'locale' => $this->request->language != '' && config('app.locale') != $this->request->language ? true : false,
        ]);
    }

    /**
     * Show privacy and security page
     *
     * @return Response
     */
    public function privacySecurity()
    {
        $sessions = \DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderBy('id', 'DESC')
            ->first();

        return view('users.privacy_security')
            ->with('sessions', $sessions)
            ->with('current_session_id', \Session::getId());
    }

    /**
     * Save privacy and security settings
     *
     * @return Response
     */
    public function savePrivacySecurity()
    {
        $user = User::find(auth()->id());
        $user->hide_profile = $this->request->hide_profile ?? 'no';
        $user->hide_last_seen = $this->request->hide_last_seen ?? 'no';
        $user->hide_count_subscribers = $this->request->hide_count_subscribers ?? 'no';
        $user->hide_my_country = $this->request->hide_my_country ?? 'no';
        $user->show_my_birthdate = $this->request->show_my_birthdate ?? 'no';
        $user->active_status_online = $this->request->active_status_online ?? 'no';
        $user->two_factor_auth = $this->request->two_factor_auth ?? 'no';
        $user->save();

        return redirect('privacy/security')->withStatus(trans('admin.success_update'));
    }

    /**
     * Logout a session based on session id
     *
     * @param string $id
     * @return Response
     */
    public function logoutSession($id)
    {
        \DB::table('sessions')
            ->where('id', $id)->delete();

        return redirect('privacy/security');
    }
}
