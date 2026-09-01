<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\SubscribePrice;

use App\Enums\PermissionManagementPermissions;
use App\Enums\PriceInterval;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Central\Models\SubscriptionPrice;

class StoreSubscriptionPriceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'interval' => ['required', 'in:' . implode(',', array_column(PriceInterval::cases(), 'value'))],
            'stripe_price_id' => ['nullable', 'integer', 'max:170'],
            'is_active' => ['sometimes', 'boolean'],
            'has_trial' => ['sometimes', 'boolean'],
            'traily_days' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'create',
            SubscriptionPrice::class
        );
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
            'has_trial' => 'Subscription Plan Has Trial',
            'trialy_days' => 'Subscription Plan Trial Days',
        ];
    }


    public function  messages()
    {
        return [
            'plan_id.required' => 'The :attributes Must Be required.',
            'plan_id.exists' => 'The :attributes Must Be Contain.',
            //
            'price.required' => 'The :attributes Must Be required.',
            'price.numeric' => 'The :attributes Must Be Numeric.',
            'price.min' => 'The :attributes Must Be Minimum 0.00.',

            'interval.required' => 'The :attributes Must Be required.',
            'interval.in' => 'The :attributes Must Be Contain.',

            'stripe_price_id.integer' => 'The :attributes Must Be Integer.',

            'is_active.boolean' => 'The :attributes Must Be Boolean.',

            'has_trial.boolean' => 'The :attributes Must Be Boolean.',

            'traily_days.integer' => 'The :attributes Must Be Integer.',

            'traily_days.min' => 'The :attributes Must Be Minimum 0.',
        ];
    }
}
