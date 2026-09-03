<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Department;

use App\Http\Requests\BaseRequest;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\Department;
use Modules\Tenant\Models\TenantUser;

class StoreDepartmentRequest extends BaseRequest
{
    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => [
                'en' => $this->name_en,
                'ar' => $this->name_ar,
            ],

            'description' => [
                'en' => $this->description_en ?? null,
                'ar' => $this->description_ar ?? null,
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255', 'unique:departments,name->en'],
            'name.ar' => ['required', 'string', 'max:255', 'unique:departments,name->ar'],

            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string', 'max:255'],



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

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.en.required' => 'The :attribute field is required.',
            'name.ar.required' => 'The :attribute field is required.',

            'name.ar.unique' => 'The Arabic name has already been taken.',

            'name.en.string' => 'The :attribute field must be a string.',
            'name.ar.string' => 'The :attribute field must be a string.',

            'name.en.max' => 'The :attribute field must not exceed 255 characters.',
            'name.ar.max' => 'The :attribute field must not exceed 255 characters.',
            'name.en.unique' => 'The English name has already been taken.',

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

    /**
     * Get custom attribute names.
     */
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
}
