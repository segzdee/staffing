<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\User;
use App\Models\VerificationRequests;
use Illuminate\Support\Facades\Storage;
use Mail;

class VerificationController extends Controller
{
    protected $settings;

    public function __construct()
    {
        try {
            $this->settings = \App\Models\AdminSettings::first();
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    /**
     * Show member verification requests
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $data = VerificationRequests::orderBy('id', 'desc')->get();

        return view('admin.verification')->withData($data);
    }

    /**
     * Process verification request (approve/delete)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function send($action, $id, $user)
    {
        $member = User::find($user);
        $pathImage = config('path.verification');

        if (! isset($member)) {

            $sql = VerificationRequests::findOrFail($id);

            if ($sql) {
                // Delete Image
                Storage::delete($pathImage.$sql->image);

                // Delete Form W-9
                Storage::delete($pathImage.$sql->form_w9);

                $sql->delete();

                \Session::flash('success', trans('admin.success_update'));

                return redirect('panel/admin/verification/members');
            }
        }

        // Data Email Send
        $sender = $this->settings?->email_no_reply ?? config('mail.from.address');
        $titleSite = $this->settings?->title ?? config('app.name');
        $fullNameUser = $member->name;
        $emailUser = $member->email;

        if ($action == 'approve') {

            $result = VerificationRequests::whereId($id)->whereUserId($user)->firstOrFail();

            if ($result->status == 'pending') {
                $sql = VerificationRequests::whereId($id)->whereUserId($user)->whereStatus('pending')->firstOrFail();
                $sql->status = 'approved';
                $sql->save();

                // Update status verify of user
                $member->verified_id = 'yes';
                $member->save();

                // Send Email to User
                Mail::send(
                    'emails.account_verification',
                    [
                        'body' => trans('general.body_account_verification_approved'),
                        'title_site' => $titleSite,
                        'fullname' => $fullNameUser,
                    ],
                    function ($message) use ($sender, $fullNameUser, $titleSite, $emailUser) {
                        $message->from($sender, $titleSite)
                            ->to($emailUser, $fullNameUser)
                            ->subject(trans('general.account_verification_approved').' - '.$titleSite);
                    }
                );

                \Session::flash('success', trans('admin.success_update'));

                return redirect('panel/admin/verification/members');
            } else {
                \Session::flash('success', trans('admin.success_update'));

                return redirect('panel/admin/verification/members');
            }

        } elseif ($action == 'delete') {
            $sql = VerificationRequests::findOrFail($id);

            // Delete Image
            Storage::delete($pathImage.$sql->image);

            // Delete Form W-9
            Storage::delete($pathImage.$sql->form_w9);

            $sql->delete();

            // Update status verify of user
            $member->verified_id = 'reject';
            $member->save();

            // Send Email to User
            Mail::send('emails.account_verification', [
                'body' => trans('general.body_account_verification_reject'),
                'title_site' => $titleSite,
                'fullname' => $fullNameUser,
            ],
                function ($message) use ($sender, $fullNameUser, $titleSite, $emailUser) {
                    $message->from($sender, $titleSite)
                        ->to($emailUser, $fullNameUser)
                        ->subject(trans('general.account_verification_not_approved').' - '.$titleSite);
                });

            \Session::flash('success', trans('admin.success_update'));

            return redirect('panel/admin/verification/members');
        }
    }

    /**
     * Get verification file
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getFile($filename)
    {
        $filename = config('path.verification').$filename;

        return Storage::download($filename, null, [], null);
    }
}
