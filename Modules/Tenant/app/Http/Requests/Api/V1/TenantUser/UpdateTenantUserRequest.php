<?php

namespace Modules\Tenant\Http\Requests\Api\V1\TenantUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Policies\TenantUserPolicy;

class UpdateTenantUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'is_active' => [
                'required',
                'boolean',
            ],
            'department_ids' => [
                'sometimes',
                'array',

            ],
            'department_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tenant.departments', 'id'),
            ],
            'position_ids' => [
                'sometimes',
                'array',
                'min:1',
            ],

            'position_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tenant.positions', 'id'),
            ],
            'team_ids' => [
                'sometimes',
                'array',
                'min:1',
            ],
            'team_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tenant.teams', 'id'),
            ]

        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tenantUser'));
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'is_active.required' => 'The :attribute is required.',
            'is_active.boolean' => 'The :attribute must be a boolean.',


            'department_ids.array' => 'The :attribute must be an array.',
            'department_ids.*.integer' => 'Each department ID must be an integer.',
            'department_ids.*.distinct' => 'The department IDs must be unique.',
            'department_ids.*.exists' => 'One or more selected departments do not exist.',


            'position_ids.array' => 'The positions must be an array.',
            'position_ids.min' => 'At least one position must be selected.',
            'position_ids.*.integer' => 'Each position ID must be an integer.',
            'position_ids.*.distinct' => 'The position IDs must be unique.',
            'position_ids.*.exists' => 'One or more selected positions do not exist.',

            'team_ids.array' => 'A Teams must be array',
            'team_ids.min' => 'At least one team must be selected.',
            'team_ids.*.integer' => 'Each team ID must be an integer.',
            'team_ids.*.distinct' => 'The team IDs must be unique.',
            'team_ids.*.exists' => 'One or more selected teams do not exist.',
        ];
    }
    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'is_active' => 'Active',
            'department_ids.*' => 'Department',
            'position_ids' => 'Positions',
            'position_ids.*' => 'Position',
            'team_ids' => 'Teams'
        ];
    }
}
