<?php

namespace App\Http\Controllers\Admin;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Image;

class ThemeController extends Controller
{
    protected $settings;

    public function __construct(AdminSettings $settings)
    {
        $this->settings = $settings::first();
    }

    /**
     * Show theme settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('admin.theme');
    }

    /**
     * Store theme settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $temp = 'temp/';
        $path = 'img/';
        $pathAvatar = config('path.avatar');

        $rules = [
            'logo' => 'mimes:png,svg',
            'logo_blue' => 'mimes:png,svg',
            'favicon' => 'mimes:png,svg',
            'color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'navbar_background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'navbar_text_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_background_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_text_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];

        $this->validate($request, $rules);

        // ======= LOGO
        if ($request->hasFile('logo')) {

            $extension = $request->file('logo')->getClientOriginalExtension();
            $file = 'logo-'.time().'.'.$extension;

            if ($request->file('logo')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->logo);
            }

            $this->settings->logo = $file;
            $this->settings->save();
        }

        // ======= LOGO BLUE
        if ($request->hasFile('logo_2')) {

            $extension = $request->file('logo_2')->getClientOriginalExtension();
            $file = 'logo_2-'.time().'.'.$extension;

            if ($request->file('logo_2')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->logo_2);
            }

            $this->settings->logo_2 = $file;
            $this->settings->save();
        }

        // ======== FAVICON
        if ($request->hasFile('favicon')) {

            $extension = $request->file('favicon')->getClientOriginalExtension();
            $file = 'favicon-'.time().'.'.$extension;

            if ($request->file('favicon')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->favicon);
            }

            $this->settings->favicon = $file;
            $this->settings->save();
        }

        // ======== Image Header
        if ($request->hasFile('index_image_top')) {

            $extension = $request->file('index_image_top')->getClientOriginalExtension();
            $file = 'home_index-'.time().'.'.$extension;

            if ($request->file('index_image_top')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->home_index);
            }

            $this->settings->home_index = $file;
            $this->settings->save();
        }

        // ======== Background
        if ($request->hasFile('background')) {

            $extension = $request->file('background')->getClientOriginalExtension();
            $file = 'background-'.time().'.'.$extension;

            if ($request->file('background')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->background);
            }

            $this->settings->bg_gradient = $file;
            $this->settings->save();
        }

        // ======== Image on index 1
        if ($request->hasFile('image_index_1')) {

            $extension = $request->file('image_index_1')->getClientOriginalExtension();
            $file = 'image_index_1-'.time().'.'.$extension;

            if ($request->file('image_index_1')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->img_1);
            }

            $this->settings->img_1 = $file;
            $this->settings->save();
        }

        // ======== Image on index 2
        if ($request->hasFile('image_index_2')) {

            $extension = $request->file('image_index_2')->getClientOriginalExtension();
            $file = 'image_index_2-'.time().'.'.$extension;

            if ($request->file('image_index_2')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->img_2);
            }

            $this->settings->img_2 = $file;
            $this->settings->save();
        }

        // ======== Image on index 3
        if ($request->hasFile('image_index_3')) {

            $extension = $request->file('image_index_3')->getClientOriginalExtension();
            $file = 'image_index_3-'.time().'.'.$extension;

            if ($request->file('image_index_3')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->img_3);
            }

            $this->settings->img_3 = $file;
            $this->settings->save();
        }

        // ======== Image on index 4
        if ($request->hasFile('image_index_4')) {

            $extension = $request->file('image_index_4')->getClientOriginalExtension();
            $file = 'image_index_4-'.time().'.'.$extension;

            if ($request->file('image_index_4')->move($temp, $file)) {
                \File::copy($temp.$file, $path.$file);
                \File::delete($temp.$file);
                // Delete old
                \File::delete($path.$this->settings->img_4);
            }

            $this->settings->img_4 = $file;
            $this->settings->save();
        }

        // ======== Avatar
        if ($request->hasFile('avatar')) {

            $extension = $request->file('avatar')->getClientOriginalExtension();
            $file = 'default-'.time().'.'.$extension;

            $imgAvatar = Image::make($request->file('avatar'))->fit(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode($extension);

            // Copy folder
            Storage::put($pathAvatar.$file, $imgAvatar, 'public');

            // Update Avatar all users
            User::where('avatar', $this->settings->avatar)->update([
                'avatar' => $file,
            ]);

            // Delete old Avatar
            Storage::delete(config('path.avatar').$this->settings->avatar);

            $this->settings->avatar = $file;
            $this->settings->save();
        }

        // ======== Cover
        if ($request->hasFile('cover_default')) {

            $pathCover = config('path.cover');
            $extension = $request->file('cover_default')->getClientOriginalExtension();
            $file = 'cover_default-'.time().'.'.$extension;

            $request->file('cover_default')->storePubliclyAs($pathCover, $file);

            // Update Cover all users
            User::where('cover', $this->settings->cover_default)
                ->orWhere('cover', '')
                ->update([
                    'cover' => $file,
                ]);

            // Delete old Avatar
            Storage::delete($pathCover.$this->settings->cover_default);

            $this->settings->cover_default = $file;
            $this->settings->save();
        }

        // Update Color Default, and Button style
        $this->settings->whereId(1)
            ->update([
                'home_style' => $request->get('home_style'),
                'color_default' => $request->get('color'),
                'navbar_background_color' => $request->get('navbar_background_color'),
                'navbar_text_color' => $request->get('navbar_text_color'),
                'footer_background_color' => $request->get('footer_background_color'),
                'footer_text_color' => $request->get('footer_text_color'),
                'button_style' => $request->get('button_style'),
            ]);

        \Artisan::call('cache:clear');
        \Artisan::call('view:clear');

        return redirect('panel/admin/theme')
            ->with('success', trans('admin.success_update'));
    }

    /**
     * Save PWA settings
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function pwa(Request $request)
    {
        $allImgs = $request->file('files');

        if ($allImgs) {
            foreach ($allImgs as $key => $file) {

                $filename = md5(uniqid()).'.'.$file->getClientOriginalExtension();
                $file->move(public_path('images/icons'), $filename);

                \File::delete(env($key));

                $envIcon = 'images/icons/'.$filename;
                Helper::envUpdate($key, $envIcon);
            }
        }

        // Update Short Name
        Helper::envUpdate('PWA_SHORT_NAME', ' "'.$request->PWA_SHORT_NAME.'" ', true);

        return back()->withSuccessMessage(trans('admin.success_update'));
    }
}
