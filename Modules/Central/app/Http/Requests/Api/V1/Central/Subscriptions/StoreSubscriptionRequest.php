<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\Subscriptions;

use App\Enums\PermissionManagementPermissions;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Central\Models\Subscription;

class StoreSubscriptionRequest extends FormRequest
{

    /**
     * Prepare the data for validation.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'status' => SubscriptionStatus::PENDING->value,
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'price_id' => [
                'required',
                Rule::exists('subscription_prices', 'id')
                    ->where('is_active', true),
            ],

            'company_id' => [
                'required',
                Rule::exists('companies', 'id')
                    ->where('is_active', true),
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'create',
            Subscription::class
        );
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'price_id.required' => 'The :attribute field is required.',
            'company_id.required' => 'The :attribute field is required.',
            'price_id.exists' => 'The selected :attribute is invalid or inactive.',
            'company_id.exists' => 'The selected :attribute is invalid or inactive.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'price_id' => 'Subscription Price',
            'company_id' => 'Company',
        ];
    }
}
