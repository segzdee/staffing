<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
// ChatSetting model removed - this seeder is disabled
// use App\Models\ChatSetting;

class ChatSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ChatSetting model no longer exists - seeder disabled
        // This was part of the legacy content creator platform, not OvertimeStaff
        return;
        
        /* DISABLED - ChatSetting model removed
        $userList=User::where("status","active")->get();
        foreach($userList as $list){
            $checkSettingExist=ChatSetting::whereUserId($list->id)->exists();
            if(!$checkSettingExist){
                ChatSetting::create([
                    "user_id"=>$list->id,
                    "video_setting"=>"remove-never",
                    "image_setting"=>"remove-never",
                    "message_setting"=>"remove-never",
                ]);
            }
        }
        */
    }
}
