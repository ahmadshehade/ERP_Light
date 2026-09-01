<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\Company;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Central\Models\Company;

class StoreCompanyRequest extends FormRequest
{

    /**
     * Prepare the data for validation.
     *
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'name' => [
                'en' => $this->input('name_en'),
                'ar' => $this->input('name_ar'),
            ],
            'subdomain' => $this->input('subdomain'),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [

            'name.en' => ['required', 'string', 'max:125', Rule::unique('companies', 'name->en')],
            'name.ar' => ['required', 'string', 'max:125', Rule::unique('companies', 'name->ar')],
            'subdomain' => ['required', 'string', 'max:255', 'unique:companies,subdomain', 'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/'],
            'max_users' => ['required', 'integer', 'min:1', 'max:3000'],
            'photo' => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'min:2', 'max:5100'],


        ];
        if ($this->user()->hasRole(NameOfRoles::SuperAdmin->value)) {
            $rules['is_active'] = ['sometimes', 'boolean'];
        }
        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Company::class);
    }

    /**
     * Get custom messages for validator errors.
     * @return array
     */
    public function messages(): array
    {
        return [


            'subdomain.required' => 'The :attributes is required.',
            'subdomain.string' => 'The :attributes must be a string.',
            'subdomain.max' => 'The :attributes may not be greater than 255 characters.',
            'subdomain.unique' => 'The :attributes has already been taken.',

            'max_users.required' => 'The :attributes is required.',
            'max_users.integer' => 'The :attributes must be an integer.',
            'max_users.min' => 'The :attributes must be at least 1.',
            'max_users.max' => 'The :attributes may not be greater than 3000.',

            'is_active.boolean' => 'The :attributes must be a boolean.',

            'photo.file' => 'The :attributes must be a file.',
            'photo.image' => 'The :attributes must be an image.',
            'photo.max' => 'The :attributes may not be greater than 5MB.',
            'photo.min' => 'The :attributes may not be less than 2KB.',
            'photo.mimes' => 'The :attributes must be a file of type: jpeg, png, jpg, gif, webp.',

            'subdomain.regex' => 'The :attributes may only contain lowercase letters, numbers, and hyphens, and must not start or end with a hyphen.',




        ];
    }


    /**
     * Get custom attributes for validator errors.
     * @return array
     *
     */
    public function attributes(): array
    {
        return [
            'name.en' => 'Name (English)',
            'name.ar' => 'Name (Arabic)',
            'subdomain' => 'Subdomain',
            'max_users' => 'Maximum Number of Users',
            'is_active' => 'Status',
            'photo' => 'Company Logo',
            'regex:Regex'
        ];
    }
}
