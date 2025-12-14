<?php

namespace App\Listeners;

use App\Events\CreatorIssueResolve;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\AdminSettings;
use App\Models\User;
use Mail;


class CreateIssueResolveListener implements ShouldQueue
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
     * @param  \App\Events\CreatorIssueResolve  $event
     * @return void
     */
    public function handle(CreatorIssueResolve $event)
    {
        $settings = AdminSettings::first();
        $user=User::findOrfail($event->user_id);
        
        $sender       = $settings->email_no_reply;
        $titleSite    = $settings->title;
          $fullNameUser = $user->name;
          $report_title=$event->report_title;
          $_emailUser   = $user->email;
          $logo=$settings->logo_2;
  
          Mail::send('emails.creator-bug-solve', array(
                      "report_title"=> $report_title,
                      'title_site' => $titleSite,
                      'fullname'   => $fullNameUser,
                      "logo"=>$logo,
          ),
              function($message) use ($sender, $fullNameUser, $titleSite, $_emailUser,$report_title,$logo)
                  {
                      $message->from($sender, $titleSite)
                                        ->to($_emailUser, $fullNameUser)
                                          ->subject($report_title);
                  });
    }
}
