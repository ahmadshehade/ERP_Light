<?php

namespace App\Http\Requests\Api\V1\Permissions;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncRolesRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('adminJob');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'roles' => 'required|array',
            'roles.*' => 'required|exists:roles,id',
        ];
    }


    public function messages()
    {
        return [
            'roles.*.exists' => 'the :attributes not found',
        ];
    }

    public function attributes()
    {
        return [
            'roles' => 'Selected Roles'
        ];
    }
}
