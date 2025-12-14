<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ChatSetting;
use App\Models\MediaMessages;
use App\Models\User;
use App\Models\Messages;
use Carbon\Carbon;
use Auth;


class ChatSettingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $user;

    /**
     * Create a new job instance.
     *
     * @return void
     * 
     */
    public function __construct()
    {
     
      
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        
    //    $alluser=User::all();
    //    foreach($alluser as $user_list){
    //     $setting=\App\Models\ChatSetting::where("user_id",$user_list)->first();
       
       
    //     //message video delete user selected by user their condition
    //    if(!empty($setting)){
    //       if($setting->video_setting=="remove-after-view"){
    //           $getAllMessage=\App\Models\Messages::select("id")->whereFromUserId($setting->user_id)->get();
    //           foreach($getAllMessage as $message_list){
    //             \App\Models\MediaMessages::select("watch","1")->where("messages_id",$message_list->id)->where("type","video")->update(["is_deleted"=>"remove-after-view"]);
                 
    //           }
              
    //       }
    
    //       if($setting->video_setting=="remove-after-24"){
    //         $getAllMessage=\App\Models\Messages::select("id")->whereFromUserId($setting->user_id)->get();
    //         foreach($getAllMessage as $message_list){
    //           \App\Models\MediaMessages::where("messages_id",$message_list->id)->where("type","video")->where('created_at', '<=', Carbon::now()->subDay())->update(["is_deleted"=>"remove-after-24"]);
               
    //         }
    //       }
    
    //       //image
    //       if($setting->image_setting=="remove-after-view"){
    //         $getAllMessage=\App\Models\Messages::select("id")->whereFromUserId($setting->user_id)->get();
    //         foreach($getAllMessage as $message_list){
    //           \App\Models\MediaMessages::where("watch","1")->where("messages_id",$message_list->id)->where("type","image")->update(["is_deleted"=>"remove-after-view"]);
               
    //         }
            
    //     }
    
    //     if($setting->image_setting=="remove-after-24"){
    //       $getAllMessage=\App\Models\Messages::select("id")->whereFromUserId($setting->user_id)->get();
    //       foreach($getAllMessage as $message_list){
    //         \App\Models\MediaMessages::where("messages_id",$message_list->id)->where("type","image")->where('created_at', '<=', Carbon::now()->subDay())->update(["is_deleted"=>"remove-after-24"]);
             
    //       }
    //     }
    
    //     //delete text message
    //     if($setting->message_setting=="remove-after-view"){
    //         $getAllMessage=\App\Models\Messages::whereFromUserId($setting->user_id)->where('created_at', '<=', Carbon::now()->subDay())->update(["is_deleted"=>"remove-after-view"]);
           
            
    //     }
    
    //     if($setting->message_setting=="remove-after-24"){
    //       $getAllMessage=\App\Models\Messages::whereFromUserId($setting->user_id)->where('created_at', '<=', Carbon::now()->subDay())->update(["is_deleted"=>"remove-after-24"]);
          
    //     }
          
    //    }
    //    }
        
    }
}
