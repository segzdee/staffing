<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\ChatSetting;

class ContentSettingsController extends Controller
{
  public function __construct(
    public Request $request,
    public AdminSettings $settings
  ) {
    $this->settings = $settings::first();
  }

  /**
   * Display video settings page
   *
   * @return \Illuminate\View\View
   */
  public function videoSettings()
  {
    return view("users.video_setting");
  }

  /**
   * Update video content settings
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateVideoSettings()
  {
    try {
      $checkSettingExist = ChatSetting::whereUserId(auth()->user()->id)->exists();

      if (!$checkSettingExist) {
        $createSetting = ChatSetting::create([
          "user_id" => auth()->user()->id,
          "video_setting" => $this->request->get("video-setting"),
        ]);

        if ($createSetting) {
          return redirect()->back()->with("success", "Updated successfully");
        } else {
          return redirect()->back()->with("error", "Failed try again");
        }
      } else {
        $update = ChatSetting::whereUserId(auth()->user()->id)
          ->update(['video_setting' => $this->request->get("video-setting")]);

        if ($update) {
          return redirect()->back()->with("success", "Updated successfully");
        } else {
          return redirect()->back()->with("error", "Failed try again");
        }
      }
    } catch(\Exception $e) {
      return redirect()->back()->with("error_message", $e->getMessage());
    }
  }

  /**
   * Display photo settings page
   *
   * @return \Illuminate\View\View
   */
  public function photoSettings()
  {
    return view("users.image_setting");
  }

  /**
   * Update photo content settings
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updatePhotoSettings()
  {
    try {
      $checkSettingExist = ChatSetting::whereUserId(auth()->user()->id)->exists();

      if (!$checkSettingExist) {
        $createSetting = ChatSetting::create([
          "user_id" => auth()->user()->id,
          "image_setting" => $this->request->get("image-setting"),
        ]);

        if ($createSetting) {
          return redirect()->back()->with("success", "Updated successfully");
        } else {
          return redirect()->back()->with("error", "Failed try again");
        }
      } else {
        $update = ChatSetting::whereUserId(auth()->user()->id)
          ->update(['image_setting' => $this->request->get("image-setting")]);

        if ($update) {
          return redirect()->back()->with("success", "Updated successfully");
        } else {
          return redirect()->back()->with("error", "Failed try again");
        }
      }
    } catch(\Exception $e) {
      return redirect()->back()->with("error_message", $e->getMessage());
    }
  }

  /**
   * Display message settings page
   *
   * @return \Illuminate\View\View
   */
  public function messageSettings()
  {
    return view("users.message_setting");
  }

  /**
   * Update message content settings
   *
   * @return \Illuminate\Http\RedirectResponse
   */
  public function updateMessageSettings()
  {
    try {
      $checkSettingExist = ChatSetting::whereUserId(auth()->user()->id)->exists();

      if (!$checkSettingExist) {
        $createSetting = ChatSetting::create([
          "user_id" => auth()->user()->id,
          "message_setting" => $this->request->get("message-setting"),
        ]);

        if ($createSetting) {
          return redirect()->back()->with("success", "Updated successfully");
        } else {
          return redirect()->back()->with("error", "Failed try again");
        }
      } else {
        $update = ChatSetting::whereUserId(auth()->user()->id)
          ->update(['message_setting' => $this->request->get("message-setting")]);

        if ($update) {
          return redirect()->back()->with("success", "Updated successfully");
        } else {
          return redirect()->back()->with("error", "Failed try again");
        }
      }
    } catch(\Exception $e) {
      return redirect()->back()->with("error_message", $e->getMessage());
    }
  }
}
