<?php

namespace Modules\Central\Http\Requests\Api\V1\Central;

use App\Enums\PermissionManagementPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Central\Models\SubscriptionPlan;

class StoreSubscriptionPlanRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => [
                'en' => $this->input('name_en'),
                'ar' => $this->input('name_ar'),
            ],
            'description' => [
                'en' => $this->input('description_en'),
                'ar' => $this->input('description_ar'),
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [

            // Name
            'name' => ['required', 'array'],
            'name.en' => ['required', 'string', 'max:255', Rule::unique('subscription_plans', 'name->en')],
            'name.ar' => ['required', 'string', 'max:255', Rule::unique('subscription_plans', 'name->ar')],

            // Description
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.ar' => ['nullable', 'string'],

            // Status
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'create',
            SubscriptionPlan::class
        );
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The :attribute field is required.',
            'name.array' => 'The :attribute must be a valid array.',

            'name.en.required' => 'The :attribute field is required.',
            'name.en.string' => 'The :attribute must be a string.',
            'name.en.max' => 'The :attribute may not be greater than :max characters.',

            'name.ar.required' => 'The :attribute field is required.',
            'name.ar.string' => 'The :attribute must be a string.',
            'name.ar.max' => 'The :attribute may not be greater than :max characters.',

            'name.ar.unique' => 'The :attribute has already been taken.',
            'name.en.unique' => 'The :attribute has already been taken.',

            'description.array' => 'The :attribute must be a valid array.',
            'description.en.string' => 'The :attribute must be a string.',
            'description.ar.string' => 'The :attribute must be a string.',

            'is_active.required' => 'The :attribute field is required.',
            'is_active.boolean' => 'The :attribute must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name.en' => 'Subscription Plan Name (English)',
            'name.ar' => 'Subscription Plan Name (Arabic)',

            'description.en' => 'Subscription Plan Description (English)',
            'description.ar' => 'Subscription Plan Description (Arabic)',

            'is_active' => 'Subscription Plan Status',
        ];
    }
}
