<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\AdminSettings;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check() && auth()->user()->verified_id == 'yes';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $settings = AdminSettings::first();
        $maxFileSize = $settings->file_size_allowed ?? 5120;

        $rules = [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'price' => ['required', 'numeric', 'min:' . ($settings->min_ppv_amount ?? 1), 'max:99999'],
            'type' => ['required', Rule::in(['digital', 'physical', 'custom'])],
            'category_id' => ['required', 'exists:categories,id'],
            'stock' => ['required_if:type,physical', 'nullable', 'integer', 'min:0'],
            'delivery_time' => ['required_if:type,physical', 'nullable', 'integer', 'min:1', 'max:365'],
            'tags' => ['nullable', 'string', 'max:500'],

            // Media
            'image' => ['required', 'image', 'mimes:jpg,gif,png,jpe,jpeg,webp', 'max:' . $maxFileSize],
            'preview' => ['nullable', 'file', 'mimes:jpg,gif,png,jpe,jpeg,webp,pdf', 'max:' . $maxFileSize],
            'file' => ['required_if:type,digital', 'nullable', 'file', 'max:' . ($maxFileSize * 10)],
        ];

        // For updates (PATCH/PUT)
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['image'] = ['sometimes', 'image', 'mimes:jpg,gif,png,jpe,jpeg,webp', 'max:' . $maxFileSize];
            $rules['file'] = ['sometimes', 'file', 'max:' . ($maxFileSize * 10)];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Product name is required.',
            'description.required' => 'Product description is required.',
            'description.min' => 'Product description must be at least 10 characters.',
            'price.required' => 'Product price is required.',
            'type.in' => 'Invalid product type.',
            'category_id.exists' => 'Selected category does not exist.',
            'stock.required_if' => 'Stock quantity is required for physical products.',
            'file.required_if' => 'Digital file is required for digital products.',
        ];
    }
}
