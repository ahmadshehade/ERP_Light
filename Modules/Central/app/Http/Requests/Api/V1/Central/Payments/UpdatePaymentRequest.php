<?php

namespace Modules\Central\Http\Requests\Api\V1\Central\Payments;

use App\Enums\PermissionManagementPermissions;
use App\Http\Requests\BaseRequest;

class UpdatePaymentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'metadata' => [
                'sometimes',
                'array',
            ],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()
            ->can('update', $this->route('payment'));
    }
}
