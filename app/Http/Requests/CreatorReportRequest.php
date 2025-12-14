<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatorReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "title"=>"required",
            "type"=>"in:bug,suggestion,general,payment-issue",
            "message"=>"required|max:500",

        ];
    }

    public function messages(){
        return [
            "title.required"=>"Title can`t be empty",
            "type.in"=>"Select type",
            "message.required"=>"Message can`t be empty",
            "message.max"=>"Message character should be less than 500 ",
        ];
    }
}
