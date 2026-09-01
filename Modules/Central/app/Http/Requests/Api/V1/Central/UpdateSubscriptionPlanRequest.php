<?php

namespace Modules\Central\Http\Requests\Api\V1\Central;

use App\Enums\PermissionManagementPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
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
        $subscriptionPlan = $this->route('subscriptionPlan');

        return [
            'name' => ['required', 'array'],

            'name_en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subscription_plans', 'name->en')
                    ->ignore($subscriptionPlan),
            ],

            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subscription_plans', 'name->ar')
                    ->ignore($subscriptionPlan),
            ],

            'description' => ['nullable', 'array'],

            'description_en' => ['nullable', 'string'],

            'description_ar' => ['nullable', 'string'],

            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('subscriptionPlan')
        );
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name_en.required' => 'The English subscription plan name is required.',
            'name_en.unique' => 'The English subscription plan name has already been taken.',

            'name_ar.required' => 'The Arabic subscription plan name is required.',
            'name_ar.unique' => 'The Arabic subscription plan name has already been taken.',

            'description_en.string' => 'The English description must be a string.',
            'description_ar.string' => 'The Arabic description must be a string.',

            'is_active.required' => 'The subscription plan status is required.',
            'is_active.boolean' => 'The subscription plan status must be true or false.',
        ];
    }

    /**
     * Custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name_en' => 'Subscription Plan Name (English)',
            'name_ar' => 'Subscription Plan Name (Arabic)',
            'description_en' => 'Subscription Plan Description (English)',
            'description_ar' => 'Subscription Plan Description (Arabic)',
            'is_active' => 'Subscription Plan Status',
        ];
    }
}
