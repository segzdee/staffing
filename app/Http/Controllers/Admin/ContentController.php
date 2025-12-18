<?php

namespace App\Http\Controllers\Admin;

use App\Helper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\Blogs;
use App\Models\Like;
use App\Models\Media;
use App\Models\Notifications;
use App\Models\Reports;
use App\Models\Updates;
use App\Notifications\PostRejected;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Image;

class ContentController extends Controller
{
    protected $settings;

    public function __construct(AdminSettings $settings)
    {
        $this->settings = $settings::first();
    }

    /**
     * Show posts
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function posts(Request $request)
    {
        $data = Updates::orderBy('id', 'desc')->paginate(20);
        $sort = $request->input('sort');

        if (request('sort') == 'pending') {
            $data = Updates::whereStatus('pending')->orderBy('id', 'desc')->paginate(20);
        }

        return view('admin.posts', ['data' => $data, 'sort' => $sort]);
    }

    /**
     * Delete a post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deletePost(Request $request)
    {
        $sql = Updates::findOrFail($request->id);
        $path = config('path.images');
        $pathVideo = config('path.videos');
        $pathMusic = config('path.music');
        $pathFile = config('path.files');

        if ($sql->status == 'pending') {
            try {
                $sql->user()->notify(new PostRejected($sql));
            } catch (\Exception $e) {
                \Log::info($e->getMessage());
            }
        }

        $files = Media::whereUpdatesId($sql->id)->get();

        foreach ($files as $media) {

            if ($media->image) {
                Storage::delete($path.$media->image);
                $media->delete();
            }

            if ($media->video) {
                Storage::delete($pathVideo.$media->video);
                Storage::delete($pathVideo.$media->video_poster);
                $media->delete();
            }

            if ($media->music) {
                Storage::delete($pathMusic.$media->music);
                $media->delete();
            }

            if ($media->file) {
                Storage::delete($pathFile.$media->file);
                $media->delete();
            }

            if ($media->video_embed) {
                $media->delete();
            }
        }

        // Delete Reports
        $reports = Reports::where('report_id', $request->id)->where('type', 'update')->get();

        if (isset($reports)) {
            foreach ($reports as $report) {
                $report->delete();
            }
        }

        // Delete Notifications
        Notifications::where('target', $request->id)
            ->where('type', '2')
            ->orWhere('target', $request->id)
            ->where('type', '3')
            ->orWhere('target', $request->id)
            ->where('type', '6')
            ->orWhere('target', $request->id)
            ->where('type', '7')
            ->orWhere('target', $request->id)
            ->where('type', '8')
            ->orWhere('target', $request->id)
            ->where('type', '9')
            ->delete();

        // Delete Likes Comments
        foreach ($sql->comments()->get() as $key) {
            $key->likes()->delete();
        }

        // Delete Comments
        $sql->comments()->delete();

        // Delete likes
        Like::where('updates_id', $request->id)->delete();

        $sql->delete();

        return redirect('panel/admin/posts');
    }

    /**
     * Approve a post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approvePost(Request $request)
    {
        $post = Updates::findOrFail($request->id);
        $post->date = now();
        $post->status = 'active';
        $post->save();

        // Notify to user - destination, author, type, target
        Notifications::send($post->user_id, 1, 8, $post->id);

        return back()->withSuccessMessage(trans('general.approve_post_success'));
    }

    /**
     * Show reports
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function reports()
    {
        $data = Reports::orderBy('id', 'desc')->get();

        return view('admin.reports')->withData($data);
    }

    /**
     * Delete a report
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteReport(Request $request)
    {
        $report = Reports::findOrFail($request->id);
        $report->delete();

        return redirect('panel/admin/reports');
    }

    /**
     * Upload image for editor
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImageEditor(Request $request)
    {
        if ($request->hasFile('upload')) {

            $path = config('path.admin');

            $validator = Validator::make($request->all(), [
                'upload' => 'required|mimes:jpg,gif,png,jpe,jpeg|max:'.$this->settings->file_size_allowed.'',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'uploaded' => 0,
                    'error' => ['message' => trans('general.upload_image_error_editor').' '.Helper::formatBytes($this->settings->file_size_allowed * 1024)],
                ]);
            }

            $originName = $request->file('upload')->getClientOriginalName();
            $fileName = pathinfo($originName, PATHINFO_FILENAME);
            $extension = $request->file('upload')->getClientOriginalExtension();
            $fileName = Str::random().'_'.time().'.'.$extension;

            $request->file('upload')->storePubliclyAs($path, $fileName);

            $CKEditorFuncNum = $request->input('CKEditorFuncNum');
            $url = Helper::getFile($path.$fileName);
            $msg = 'Image uploaded successfully';
            $response = "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$msg');</script>";

            return response()->json(['fileName' => $fileName, 'uploaded' => true, 'url' => $url]);
        }
    }

    /**
     * Show blog posts
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function blog()
    {
        $data = Blogs::orderBy('id', 'desc')->paginate(50);

        return view('admin.blog', ['data' => $data]);
    }

    /**
     * Store a blog post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function createBlogStore(Request $request)
    {
        $path = config('path.admin');

        $rules = [
            'title' => 'required',
            'thumbnail' => 'required|dimensions:min_width=650,min_height=430',
            'tags' => 'required',
            'content' => 'required',
        ];

        $this->validate($request, $rules);

        // Image
        if ($request->hasFile('thumbnail')) {

            $image = $request->file('thumbnail');
            $extension = $image->getClientOriginalExtension();
            $thumbnail = Str::random(55).'.'.$extension;

            $imageResize = Image::make($image)->orientate()->resize(650, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode($extension);

            Storage::put($path.$thumbnail, $imageResize, 'public');
        }

        $data = new Blogs;
        $data->slug = str_slug($request->title);
        $data->title = $request->title;
        $data->image = $thumbnail;
        $data->tags = $request->tags;
        $data->content = $request->content;
        $data->user_id = auth()->user()->id;
        $data->save();

        \Session::flash('success', trans('admin.success_add'));

        return redirect('panel/admin/blog');
    }

    /**
     * Show edit blog post form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function editBlog($id)
    {
        $data = Blogs::findOrFail($id);

        return view('admin.edit-blog', ['data' => $data]);
    }

    /**
     * Update a blog post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBlog(Request $request)
    {
        $data = Blogs::findOrFail($request->id);

        $path = config('path.admin');

        $rules = [
            'title' => 'required',
            'thumbnail' => 'dimensions:min_width=650,min_height=430',
            'tags' => 'required',
            'content' => 'required',
        ];

        $this->validate($request, $rules);

        $thumbnail = $data->image;

        // Image
        if ($request->hasFile('thumbnail')) {

            $image = $request->file('thumbnail');
            $extension = $image->getClientOriginalExtension();
            $thumbnail = Str::random(55).'.'.$extension;

            $imageResize = Image::make($image)->orientate()->resize(650, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode($extension);

            Storage::put($path.$thumbnail, $imageResize, 'public');

            // Delete Old Thumbnail
            Storage::delete($path.$data->image);
        }

        $data->title = $request->title;
        $data->slug = str_slug($request->title);
        $data->image = $thumbnail;
        $data->tags = $request->tags;
        $data->content = $request->content;
        $data->save();

        return back()->withSuccessMessage(trans('admin.success_update'));
    }

    /**
     * Delete a blog post
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteBlog($id)
    {
        $data = Blogs::findOrFail($id);

        $path = config('path.admin');

        // Delete Old Thumbnail
        Storage::delete($path.$data->image);

        $data->delete();

        return redirect('panel/admin/blog')->withSuccessMessage(trans('admin.blog_deleted'));
    }
}
