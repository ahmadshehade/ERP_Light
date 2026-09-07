<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\Company;

use App\Enums\NameOfRoles;
use App\Http\Requests\BaseRequest;

use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends BaseRequest
{
    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        $company = $this->route('company');

        $nameEn = $this->input('name_en')
            ?? $company->getTranslation('name', 'en');

        $nameAr = $this->input('name_ar')
            ?? $company->getTranslation('name', 'ar');

        $subdomain = $this->has('subdomain')
            ? $this->input('subdomain')
            : $company->subdomain;

        $this->merge([
            'name' => [
                'en' => $nameEn,
                'ar' => $nameAr,
            ],
            'subdomain' => $subdomain,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $company = $this->route('company');

        $rules = [
            'name' => ['array'],

            'name.en' => [
                'sometimes',
                'string',
                'max:125',
                Rule::unique('companies', 'name->en')->ignore($company->id),
            ],

            'name.ar' => [
                'sometimes',
                'string',
                'max:125',
                Rule::unique('companies', 'name->ar')->ignore($company->id),
            ],

            'subdomain' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('companies', 'subdomain')->ignore($company->id),
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/'
            ],

            'max_users' => [
                'sometimes',
                'integer',
                'min:1',
                'max:3000',
            ],

            'photo' => ['sometimes', 'image', 'file', 'mimes:jpeg,png,jpg,gif,webp', 'max:5100', 'min:2'],


        ];
        $user = $this->user();
        if ($user->hasRole(NameOfRoles::SuperAdmin->value)) {
            $rules['is_active'] = ['sometimes', 'boolean'];
        }
        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('company'));
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [

            'name.en.unique' => 'The English name has already been taken.',
            'name.en.max' => 'The English name may not be greater than 125 characters.',


            'name.ar.unique' => 'The Arabic name has already been taken.',
            'name.ar.max' => 'The Arabic name may not be greater than 125 characters.',


            'subdomain.string' => 'The subdomain must be a string.',
            'subdomain.max' => 'The subdomain may not be greater than 255 characters.',
            'subdomain.unique' => 'The subdomain has already been taken.',


            'max_users.integer' => 'The maximum number of users must be an integer.',
            'max_users.min' => 'The maximum number of users must be at least 1.',
            'max_users.max' => 'The maximum number of users may not be greater than 3000.',

            'photo.file' => 'The :attributes must be a file.',
            'photo.image' => 'The :attributes must be an image.',
            'photo.max' => 'The :attributes may not be greater than 5MB.',
            'photo.min' => 'The :attributes may not be less than 2KB.',
            'photo.mimes' => 'The :attributes must be a file of type: jpeg, png, jpg, gif, webp.',

            'subdomain.regex' => 'The :attributes may only contain lowercase letters, numbers, and hyphens, and must not start or end with a hyphen.',

            'is_active.boolean' => 'The :attributes field must be a boolean.',


        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name.en' => 'Name (English)',
            'name.ar' => 'Name (Arabic)',
            'subdomain' => 'Subdomain',
            'max_users' => 'Maximum Number of Users',
            'is_active' => 'Status',
            'regex' => 'Regex',


        ];
    }
}
