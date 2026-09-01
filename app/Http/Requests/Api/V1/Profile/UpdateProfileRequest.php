<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('profile')
        );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'sometimes',
                'string',
                'min:5',
                'max:50',
                Rule::unique('profiles')
                    ->ignore($this->route('profile')->id),
            ],

            'timezone' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'language' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'birth_date' => [
                'sometimes',
                'date',
            ],

            'avatar' => [
                'sometimes',
                'file',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.string' => 'The :attribute must be a string',
            'phone.max' => 'The :attribute must not exceed 50 characters',
            'phone.unique' => 'The :attribute must be unique',
            'phone.min' => 'The :attribute must be at least 5 characters',

            'timezone.string' => 'The :attribute must be a string',
            'timezone.max' => 'The :attribute must not exceed 50 characters',

            'language.string' => 'The :attribute must be a string',
            'language.max' => 'The :attribute must not exceed 50 characters',

            'birth_date.date' => 'The :attribute must be a valid date',

            'avatar.image' => 'The :attribute must be an image',
            'avatar.file' => 'The :attribute must be a file',
            'avatar.mimes' => 'The :attribute must be jpeg, png, jpg, gif, or webp',
            'avatar.max' => 'The :attribute must not exceed 5MB',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone' => 'User Phone',
            'timezone' => 'User Timezone',
            'language' => 'User Language',
            'birth_date' => 'User Birth Date',
            'avatar' => 'User Avatar',
        ];
    }
}
