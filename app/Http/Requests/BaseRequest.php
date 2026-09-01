<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BaseRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     */
    public function failedValidation(Validator $validator)
    {
        return new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
