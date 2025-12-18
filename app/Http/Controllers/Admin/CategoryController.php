<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Image;

class CategoryController extends Controller
{
    /**
     * Show categories
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $categories = Categories::orderBy('name')->get();
        $totalCategories = count($categories);

        return view('admin.categories', compact('categories', 'totalCategories'));
    }

    /**
     * Show add category form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('admin.add-categories');
    }

    /**
     * Store a new category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $temp = 'temp/';
        $path = 'img-category/';

        Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
            return ! preg_match('/[^x00-x7F\-]/i', $value);
        });

        $rules = [
            'name' => 'required',
            'slug' => 'required|ascii_only|unique:categories',
            'thumbnail' => 'required|mimes:jpg,gif,png,jpe,jpeg|dimensions:min_width=30,min_height=30',
        ];

        $this->validate($request, $rules);

        if ($request->hasFile('thumbnail')) {

            $extension = $request->file('thumbnail')->getClientOriginalExtension();
            $thumbnail = $request->slug.'-'.Str::random(32).'.'.$extension;

            if ($request->file('thumbnail')->move($temp, $thumbnail)) {

                $image = Image::make($temp.$thumbnail);

                \File::copy($temp.$thumbnail, $path.$thumbnail);
                \File::delete($temp.$thumbnail);
            }
        } else {
            $thumbnail = '';
        }

        $sql = new Categories;
        $sql->name = $request->name;
        $sql->slug = $request->slug;
        $sql->keywords = $request->keywords;
        $sql->description = $request->description;
        $sql->mode = $request->mode;
        $sql->image = $thumbnail;
        $sql->save();

        \Session::flash('success', trans('admin.success_add_category'));

        return redirect('panel/admin/categories');
    }

    /**
     * Show edit category form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $categories = Categories::find($id);

        return view('admin.edit-categories')->with('categories', $categories);
    }

    /**
     * Update a category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $categories = Categories::find($request->id);
        $temp = 'temp/';
        $path = 'img-category/';

        if (! isset($categories)) {
            return redirect('panel/admin/categories');
        }

        Validator::extend('ascii_only', function ($attribute, $value, $parameters) {
            return ! preg_match('/[^x00-x7F\-]/i', $value);
        });

        $rules = [
            'name' => 'required',
            'slug' => 'required|ascii_only|unique:categories,slug,'.$request->id,
            'thumbnail' => 'mimes:jpg,gif,png,jpe,jpeg|dimensions:min_width=30,min_height=30',
        ];

        $this->validate($request, $rules);

        if ($request->hasFile('thumbnail')) {

            $extension = $request->file('thumbnail')->getClientOriginalExtension();
            $thumbnail = $request->slug.'-'.Str::random(32).'.'.$extension;

            if ($request->file('thumbnail')->move($temp, $thumbnail)) {

                $image = Image::make($temp.$thumbnail);

                \File::copy($temp.$thumbnail, $path.$thumbnail);
                \File::delete($temp.$thumbnail);

                // Delete Old Image
                \File::delete($path.$categories->thumbnail);
            }
        } else {
            $thumbnail = $categories->image;
        }

        // UPDATE CATEGORY
        $categories->name = $request->name;
        $categories->slug = $request->slug;
        $categories->keywords = $request->keywords;
        $categories->description = $request->description;
        $categories->mode = $request->mode;
        $categories->image = $thumbnail;
        $categories->save();

        \Session::flash('success', trans('general.success_update'));

        return redirect('panel/admin/categories');
    }

    /**
     * Delete a category
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $categories = Categories::findOrFail($id);
        $thumbnail = 'img-category/'.$categories->image;

        $userCategory = User::where('categories_id', $id)->update(['categories_id' => 0]);

        // Delete Category
        $categories->delete();

        // Delete Thumbnail
        if (\File::exists($thumbnail)) {
            \File::delete($thumbnail);
        }

        return redirect('panel/admin/categories');
    }
}
