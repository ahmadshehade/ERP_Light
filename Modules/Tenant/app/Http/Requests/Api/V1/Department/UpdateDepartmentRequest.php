<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;

class UpdateDepartmentRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $department = $this->route('department');

        $this->merge([
            'name' => [
                'en' => $this->input('name_en', $department->getTranslation('name', 'en')),
                'ar' => $this->input('name_ar', $department->getTranslation('name', 'ar')),
            ],

            'description' => [
                'en' => $this->input(
                    'description_en',
                    $department->getTranslation('description', 'en')
                ),

                'ar' => $this->input(
                    'description_ar',
                    $department->getTranslation('description', 'ar')
                ),
            ],
        ]);
    }

    public function rules(): array
    {
        $department = $this->route('department');

        $rules = [
            'name' => ['required', 'array'],

            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name->en')->ignore($department->id),
            ],

            'name.ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name->ar')->ignore($department->id),
            ],

            'description' => ['required', 'array'],

            'description.en' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description.ar' => [
                'nullable',
                'string',
                'max:255',
            ],


            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:5100',
            ],
        ];
        $user = $this->user();
        $tenatnUser = TenantUser::where('user_id', $user->id)->first();
        if ($tenatnUser->hasRole(TenantRoles::Owner->value)) {
            $rules['is_active'] = [
                'sometimes',
                'boolean',
            ];
        }
        return $rules;
    }

    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('department')
        );
    }

    public function attributes(): array
    {
        return [
            'name.en' => 'Department Name English',
            'name.ar' => 'Department Name Arabic',
            'description.en' => 'Department Description English',
            'description.ar' => 'Department Description Arabic',
            'is_active' => 'Department Activation',
            'photo' => 'Department Photo',
        ];
    }

    public function messages(): array
    {
        return [
            'name.en.required' => 'The :attribute field is required.',
            'name.ar.required' => 'The :attribute field is required.',
            'name.ar.unique' => 'The Arabic name has already been taken.',

            'name.en.string' => 'The :attribute field must be a string.',
            'name.ar.string' => 'The :attribute field must be a string.',

            'name.en.unique' => 'The English name has already been taken.',

            'name.en.max' => 'The :attribute field must not exceed 255 characters.',
            'name.ar.max' => 'The :attribute field must not exceed 255 characters.',

            'description.en.string' => 'The :attribute field must be a string.',
            'description.ar.string' => 'The :attribute field must be a string.',

            'description.en.max' => 'The :attribute field must not exceed 255 characters.',
            'description.ar.max' => 'The :attribute field must not exceed 255 characters.',

            'is_active.boolean' => 'The :attribute field must be a boolean.',

            'photo.image' => 'The :attribute field must be an image.',
            'photo.mimes' => 'The :attribute field must be a jpeg, png, jpg, or gif.',
            'photo.max' => 'The :attribute field must not exceed 5MB.',
        ];
    }
}
