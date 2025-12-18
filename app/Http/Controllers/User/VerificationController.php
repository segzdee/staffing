<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Models\AdminSettings;
use App\Models\VerificationRequests;
use App\Notifications\AdminVerificationPending;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    protected $request;
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
     * Display account verification form
     *
     * @return Response
     */
    public function verifyAccount()
    {
      return view('users.verify_account');
    }

    /**
     * Submit account verification request
     *
     * @return Response
     */
    public function verifyAccountSend()
    {
      $checkRequest = VerificationRequests::whereUserId(auth()->id())->whereStatus('pending')->first();


      if ($checkRequest) {
        return redirect()->back()
    				->withErrors([
    					'errors' => trans('admin.pending_request_verify'),
    				]);
      } elseif (auth()->user()->verified_id == 'reject') {
        return redirect()->back()
    				->withErrors([
    					'errors' => trans('admin.rejected_request'),
    				]);
      }



      $input = $this->request->all();
      $input['isUSCitizen'] = auth()->user()->countries_id;

      $messages = [
        "form_w9.required_if" => trans('general.form_w9_required')
      ];

     $validator = Validator::make($input, [
       'address'  => 'required',
       'city' => 'required',
       'zip' => 'required',
       'image' => 'required|mimes:jpg,gif,png,jpe,jpeg,zip|max:'.($this->settings?->file_size_allowed_verify_account ?? 5120).'',
       // 'form_w9'  => 'required_if:isUSCitizen,==,1|mimes:pdf|max:'.$this->settings->file_size_allowed_verify_account.'',

    ], $messages);

     if ($validator->fails()) {
         return redirect()->back()
                   ->withErrors($validator)
                   ->withInput();
     }

     // PATHS
 		$path = config('path.verification');

     if ($this->request->hasFile('image')) {

		$extension = $this->request->file('image')->getClientOriginalExtension();
		$fileImage = strtolower(auth()->id().time().Str::random(40).'.'.$extension);

     $this->request->file('image')->storePubliclyAs($path, $fileImage);

   }//<====== End HasFile

    if ($this->request->hasFile('form_w9')) {

      $extension = $this->request->file('form_w9')->getClientOriginalExtension();
      $fileFormW9 = strtolower(auth()->id().time().Str::random(40).'.'.$extension);

    $this->request->file('form_w9')->storePubliclyAs($path, $fileFormW9);

   }//<====== End HasFile

     $sql          = new VerificationRequests();
			$sql->user_id = auth()->id();
			$sql->address = $input['address'];
			$sql->city    = $input['city'];
     $sql->zip     = $input['zip'];
     $sql->image   = $fileImage;
     $sql->form_w9 = $fileFormW9 ?? '';
			$sql->save();

     // Save data user
     User::whereId(auth()->id())->update([
       'address' => $input['address'],
       'city' => $input['city'],
       'zip' => $input['zip']
     ]);

     // Notify Admin via Email
     try {
       if ($this->settings?->email_admin) {
         Notification::route('mail' , $this->settings->email_admin)
             ->notify(new AdminVerificationPending($sql));
       }
     } catch (\Exception $e) {
       \Log::info($e->getMessage());
     }

     return redirect('settings/verify/account')->withStatus(__('general.send_success_verification'));
    }
}
