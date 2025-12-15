<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class UploadController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Upload an image file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        try {
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Store in public storage
                $path = $file->storeAs('uploads/images', $filename, 'public');

                return response()->json([
                    'success' => true,
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'filename' => $filename,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image file provided.',
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image. Please try again.',
            ], 500);
        }
    }
}
