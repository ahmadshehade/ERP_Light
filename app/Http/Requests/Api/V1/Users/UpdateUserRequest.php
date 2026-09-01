<?php

namespace App\Http\Requests\Api\V1\Users;

use App\Enums\UserPermissions;
use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'string', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', 'confirmed'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'the :attributes must contain at least one letter and one number.',
            'password.min' => 'the :attributes must be at least 8 characters long.',
            'password.confirmed' => 'the :attributes confirmation does not match.',

            'name.string' => 'the :attributes must be a string.',
            'name.max' => 'the :attributes must not exceed 255 characters.',

            'email.email' => 'the :attributes must be a valid email address.',
            'email.max' => 'the :attributes must not exceed 255 characters.',
            'email.unique' => 'the :attributes already exists.',

        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'User Name',
            'email' => 'User Email',
            'password' => 'User Password'
        ];
    }
}
