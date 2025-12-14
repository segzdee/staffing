<?php

namespace App\Listeners;

use App\Events\SendMailToAdminByCreator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\AdminSettings;
use App\Models\User;
use Mail;

class SendMailToAdminByCreatorListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\SendMailToAdminByCreator  $event
     * @return void
     */
    public function handle(SendMailToAdminByCreator $event)
    {

        $settings = AdminSettings::first();
        $user=User::findOrfail($event->user_id);
        $admin_user=User::where("role","admin")->first();
        
        $sender       = $settings->email_no_reply;
        $titleSite    = $settings->title;
          $fullNameUser = $user->name;
          $report_title=$event->report_title;
          $_emailUser   = $user->email;
          $logo=$settings->logo_2;
          $files=$event->attach_files;
        Mail::send('emails.send_email_to_admin_by_creator_bug', array(
            "report_title"=> $report_title,
            'title_site' => $titleSite,
            'fullname'   => $fullNameUser,
            "logo"=>$logo,
            "admin_user"=>$admin_user,
            
          
),
    function($message) use ($sender, $fullNameUser, $titleSite, $_emailUser,$report_title,$logo,$admin_user,$files)
        {
            $message->from($_emailUser, $titleSite)
                              ->to($admin_user->email, $fullNameUser)
                                ->subject($report_title);
                                 
                            //    if(count($files)>0){
                            //     foreach($files as $image){
                            //         $message->attach($image->getRealPath(),array(
                            //             'as' => 'files'.now().rand(9,99999).'.png',
                            //             'mime' => $image->getMimeType(),
                            //         ));
                            //     }
                            //    }
        });
    }
}
