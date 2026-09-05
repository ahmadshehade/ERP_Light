<?php

namespace Modules\Tenant\Http\Requests\Api\V1\TenantUser;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\TenantUser;

class StoreTenantUserRequest extends FormRequest
{
    /**
     * define Role function
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('mysql.users', 'id'),
                Rule::unique('tenant_users', 'user_id'),
            ],

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

            ],

            'position_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tenant.positions', 'id'),
            ],

            'team_ids' => [
                'sometimes',
                'array',

            ],

            'team_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('tenant.teams', 'id'),
            ],
        ];
    }

    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', TenantUser::class);
    }

    /**
     * define messages function
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The :attribute is required.',
            'user_id.integer' => 'The :attribute must be an integer.',
            'user_id.exists' => 'The :attribute does not exist.',
            'user_id.unique' => 'The :attribute already belongs to this company.',

            'is_active.required' => 'The :attribute is required.',
            'is_active.boolean' => 'The :attribute must be a boolean.',

            // 'department_ids.required' => 'At least one department is required.',
            'department_ids.array' => 'The :attribute must be an array.',
            // 'department_ids.min' => 'At least one department must be selected.',
            'department_ids.*.integer' => 'Each department ID must be an integer.',
            'department_ids.*.distinct' => 'The department IDs must be unique.',
            'department_ids.*.exists' => 'One or more selected departments do not exist.',

            'position_ids.required' => 'At least one position is required.',
            'position_ids.array' => 'The positions must be an array.',
            // 'position_ids.min' => 'At least one position must be selected.',
            'position_ids.*.integer' => 'Each position ID must be an integer.',
            'position_ids.*.distinct' => 'The position IDs must be unique.',
            'position_ids.*.exists' => 'One or more selected positions do not exist.',

            'team_ids.required' => 'At least one team is required.',
            'team_ids.array' => 'A Teams must be array',
            // 'team_ids.min' => 'At least one team must be selected.',
            'team_ids.*.integer' => 'Each team ID must be an integer.',
            'team_ids.*.distinct' => 'The team IDs must be unique.',
            'team_ids.*.exists' => 'One or more selected teams do not exist.',
        ];
    }

    /**
     * define attributes function
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'User Identifier',
            'is_active' => 'Active',
            'department_ids' => 'Departments',
            'department_ids.*' => 'Department',
            'position_ids' => 'Positions',
            'position_ids.*' => 'Position',
            'team_ids' => 'Teams',
        ];
    }
}
