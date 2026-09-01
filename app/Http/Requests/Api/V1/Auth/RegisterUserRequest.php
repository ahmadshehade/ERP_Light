<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', 'confirmed'],

            //############  Profile  ######################
            'phone' => ['required', 'string', 'max:50', 'unique:profiles', 'min:5'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'language' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],

        ];
    }


    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'the  :attributes is required',
            'email.string' => 'the :attributes must be a string',
            'email.email' => 'the :attributes must be a valid email address',
            'email.max' => 'the :attributes must not exceed 255 characters',

            'name.string' => 'the :attributes must be a string',
            'name.required' => 'the :attributes is required',
            'name.max' => ' the :attributes must not exceed 255 characters',

            'password.required' => 'the :attributes is required',
            'password.string' => 'the :attributes must be a string',
            'password.min' => 'the :attributes must be at least 8 characters',
            'password.regex' => 'the :attributes must contain at least one uppercase letter, one lowercase letter, one digit, and one special character',
            'password.confirmed' => 'the :attributes confirmation does not match',

            //#############  Profile  ######################
            'phone.required' => 'the :attributes is required',
            'phone.string' => 'the :attributes must be a string',
            'phone.max' => 'the :attributes must not exceed 50 characters',
            'phone.unique' => 'the :attributes must be unique',
            'phone.min' => 'the :attributes must be at least 5 characters',

            'timezone.string' => 'the :attributes must be a string',
            'timezone.max' => 'the :attributes must not exceed 50 characters',

            'language.string' => 'the :attributes must be a string',
            'language.max' => 'the :attributes must not exceed 50 characters',

            'birth_date.date' => 'the :attributes must be a valid date',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => 'User Email',
            'name' => 'User Name',
            'password' => 'User Password',

            //#############  Profile  ######################
            'phone' => 'User Phone',
            'timezone' => 'User Timezone',
            'language' => 'User Language',
            'birth_date' => 'User Birth Date',
        ];
    }
}
