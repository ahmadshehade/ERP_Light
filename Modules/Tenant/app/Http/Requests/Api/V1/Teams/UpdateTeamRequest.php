<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Teams;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Models\Team;

class UpdateTeamRequest extends BaseRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $team = $this->route('team');
        $this->merge([
            'name' => [
                'en' => $this->input('name_en') ?? $team->getTranslation('name', 'en'),
                'ar' => $this->input('name_ar') ?? $team->getTranslation('name', 'ar'),
            ],

            'description' => [
                'en' => $this->input('description_en') ?? $team->getTranslation('description', 'en'),
                'ar' => $this->input('description_ar') ?? $team->getTranslation('description', 'ar'),
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'nullable',
                'array',
            ],

            'name.en' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('teams', 'name->en')->ignore($this->route('team')->id),
            ],

            'name.ar' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('teams', 'name->ar')->ignore($this->route('team')->id),
            ],

            'description' => [
                'nullable',
                'array',
            ],

            'description.en' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description.ar' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif',
                'max:5100',
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Team::class);
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [

            'name.en.string' => 'The :attribute field must be a string.',
            'name.en.max' => 'The :attribute field may not be greater than 255 characters.',
            'name.en.unique' => 'The :attribute field must be unique.',


            'name.ar.string' => 'The :attribute field must be a string.',
            'name.ar.max' => 'The :attribute field may not be greater than 255 characters.',
            'name.ar.unique' => 'The :attribute field must be unique.',



            'description.en.string' => 'The :attribute field must be a string.',
            'description.en.max' => 'The :attribute field may not be greater than 255 characters.',


            'description.ar.string' => 'The :attribute field must be a string.',
            'description.ar.max' => 'The :attribute field may not be greater than 255 characters.',


            'is_active.boolean' => 'The :attribute field must be a boolean.',

            'photo.image' => 'The :attribute field must be an image.',
            'photo.mimes' => 'The :attribute field must be a jpeg, png, jpg, or gif.',
            'photo.max' => 'The :attribute field must not exceed 5MB.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name_en' => 'Team Name (English)',
            'name_ar' => 'Team Name (Arabic)',
            'description_en' => 'Team Description (English)',
            'description_ar' => 'Team Description (Arabic)',
            'is_active' => 'Team Status',
            'photo' => 'Team Photo',
        ];
    }
}
