<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Positions;

use App\Http\Requests\BaseRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Position;

class UpdatePositionRequest extends BaseRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        /** @var Position $position */
        $position = $this->route('position');

        $this->merge([
            'name' => [
                'en' => $this->input(
                    'name_en'
                ) ?? $position->getTranslation('name', 'en'),
                'ar' => $this->input(
                    'name_ar'

                ) ?? $position->getTranslation('name', 'ar'),
            ],

            'description' => [
                'en' => $this->input(
                    'description_en'

                ) ?? $position->getTranslation('description', 'en'),
                'ar' => $this->input(
                    'description_ar'

                ) ?? $position->getTranslation('description', 'ar'),
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Position $position */
        $position = $this->route('position');

        return [
            'name' => [
                'required',
                'array',
            ],

            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name->en')
                    ->ignore($position->id),
            ],

            'name.ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name->ar')
                    ->ignore($position->id),
            ],

            'description' => [
                'nullable',
                'array',
            ],

            'description.en' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'description.ar' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('position')
        );
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Position name is required.',
            'name.array' => 'Position name must be an array.',

            'name.en.required' => 'Position name in English is required.',
            'name.en.string' => 'Position name in English must be a string.',
            'name.en.max' => 'Position name in English must not exceed 255 characters.',
            'name.en.unique' => 'Position name in English already exists.',

            'name.ar.required' => 'Position name in Arabic is required.',
            'name.ar.string' => 'Position name in Arabic must be a string.',
            'name.ar.max' => 'Position name in Arabic must not exceed 255 characters.',
            'name.ar.unique' => 'Position name in Arabic already exists.',

            'description.en.string' => 'Position description in English must be a string.',
            'description.en.max' => 'Position description in English must not exceed 1000 characters.',
            'description.ar.string' => 'Position description in Arabic must be a string.',
            'description.ar.max' => 'Position description in Arabic must not exceed 1000 characters.',

            'is_active.boolean' => 'Status must be a boolean.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'Position name',
            'name.en' => 'Position name in English',
            'name.ar' => 'Position name in Arabic',
            'description' => 'Position description',
            'description.en' => 'Position description in English',
            'description.ar' => 'Position description in Arabic',
            'is_active' => 'Status',
        ];
    }
}
