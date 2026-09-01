<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\Subscriptions;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;
use App\Enums\SubscriptionStatus;
use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReNewSubscriptionRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'price_id' => [
                'required',
                Rule::exists('subscription_prices', 'id')
                    ->where('is_active', true),
            ],
        ];

        if ($this->user()->can('manual_activate_subscription')) {
            $rules['status'] = [
                'sometimes',
                Rule::in([
                    SubscriptionStatus::ACTIVE->value,
                ]),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'price_id.required' => 'the :attributes is required.',
            'price_id.exists' => 'The selected :attribute is invalid or inactive. ',
            'status.in' => 'the :attribute is invalid.',


        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'price_id' => 'Subscription Price',
            'status' => 'Subscription Status'
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PermissionManagementPermissions::ReNewSubscriptions->value);
    }
}
