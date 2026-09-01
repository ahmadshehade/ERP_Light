<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Positions;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Position;

class StorePositionRequest extends BaseRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => [
                'en' => $this->input('name_en'),
                'ar' => $this->input('name_ar'),
            ],

            'description' => [
                'en' => $this->input('description_en'),
                'ar' => $this->input('description_ar'),
            ],


        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
            ],

            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name->en'),
            ],

            'name.ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name->ar'),
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

    public function authorize(): bool
    {
        return $this->user()->can('create', Position::class);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The :attribute is required.',
            'name.array' => 'The :attribute must be an array.',

            'name.en.required' => 'The :attribute is required.',
            'name.en.unique' => 'The :attribute already exists.',
            'name_en.max' => 'The :attribute must not exceed 255 characters.',

            'name.ar.required' => 'The :attribute is required.',
            'name.ar.unique' => 'The :attribute already exists.',
            'name_ar.max' => 'The :attribute must not exceed 255 characters.',

            'description.en.string' => 'The :attribute must be a string.',
            'description.en.max' => 'The :attribute must not exceed :max characters.',
            'description.ar.string' => 'The :attribute must be a string.',
            'description.ar.max' => 'The :attribute must not exceed :max characters.',
            'is_active.boolean' => 'The :attribute must be a boolean.',


        ];
    }

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
