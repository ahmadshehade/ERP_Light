<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\Subscriptions;

use App\Enums\NameOfRoles;
use App\Enums\PermissionManagementPermissions;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'price_id' => [
                'sometimes',
                Rule::exists('subscription_prices', 'id')
                    ->where('is_active', true),
            ],
            'company_id' => [
                'sometimes',
                Rule::exists('companies', 'id')
                    ->where('is_active', true),
            ],
        ];


        return $rules;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('subscription')
        );
    }
    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'company_id.exists' => 'The :attribute does not exist.',
            'price_id.exists' => 'The :attribute does not exist.',

        ];
    }
    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'company_id' => 'Company',
            'price_id' => 'Subscription Price',

        ];
    }
}
