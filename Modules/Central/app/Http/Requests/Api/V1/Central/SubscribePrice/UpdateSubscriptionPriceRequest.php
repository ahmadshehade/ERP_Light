<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\SubscribePrice;

use App\Enums\PermissionManagementPermissions;
use App\Enums\PriceInterval;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['sometimes', 'exists:subscription_plans,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'interval' => ['sometimes', 'in:' . implode(',', array_column(PriceInterval::cases(), 'value'))],
            'stripe_price_id' => ['nullable', 'integer', 'max:170'],
            'is_active' => ['sometimes', 'boolean'],
            'has_trial' => ['sometimes', 'boolean'],
            'trial_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }


    /**
     * Get the error messages for the defined validation rules.
     * @return array
     *
     */
    public function  messages(): array
    {
        return [
            'plan_id.exists' => 'The :attributes Must Be Contain.',
            'price.numeric' => 'The :attributes Must Be Numeric.',
            'price.min' => 'The :attributes Must Be Minimum 0.00.',
            'interval.in' => 'The :attributes Must Be Contain.',
            'stripe_price_id.integer' => 'The :attributes Must Be Integer.',
            'is_active.boolean' => 'The :attributes Must Be Boolean.',
            'has_trial' => 'The :attributes Must Be Has Trial',
            'trialy_days' => 'The :attributes Must Be Trial Days',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     * @return array
     */
    public function attributes(): array
    {
        return [
            'plan_id' => 'Subscription Plan ID',
            'price' => 'Subscription Plan Price',
            'interval' => 'Subscription Plan Interval',
            'stripe_price_id' => 'Stripe Price ID',
            'is_active' => 'Subscription Plan Status',
            'has_trial' => 'Subscription Price Has Trial',
            'trialy_days' => 'Subscription Price Trial Days',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('subscriptionPrice')
        );
    }
}
