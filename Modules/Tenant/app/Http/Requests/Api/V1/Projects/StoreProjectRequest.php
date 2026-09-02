<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Projects;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Models\Project;

class StoreProjectRequest extends BaseRequest
{
    public function prepareForValidation(): void
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

    /**
     * Get the validation rules that apply to the request.
     */
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
                Rule::unique('projects', 'name->en'),
            ],

            'name.ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name->ar'),
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
            'start_date' => [
                'required',
                'date'
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
            'priority' => [
                'required',
                'string',
                Rule::enum(ProjectPriority::class),
            ],

            'media' => [
                'sometimes',
                'array',
            ],

            'media.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,gif,pdf,txt,tex',
                'max:10240',
            ],
            'teamIds' => [
                'sometimes',
                'array',
            ],

            'teamIds.*' => [
                'integer',
                'exists:teams,id',
            ],

        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    public function messages(): array
    {
        return [
            'name.ar.required' => 'The :attribute field is required.',
            'name.ar.string' => 'The :attribute field must be a string.',
            'name.ar.max' => 'The :attribute field must not exceed 255 characters.',
            'name.ar.unique' => 'The :attribute must be unique.',

            'name.en.required' => 'The :attribute field is required.',
            'name.en.string' => 'The :attribute field must be a string.',
            'name.en.max' => 'The :attribute field must not exceed 255 characters.',
            'name.en.unique' => 'The :attribute must be unique.',

            'description.en.string' => 'The :attribute field must be a string.',
            'description.en.max' => 'The :attribute field must not exceed 255 characters.',

            'description.ar.string' => 'The :attribute field must be a string.',
            'description.ar.max' => 'The :attribute field must not exceed 255 characters.',

            'is_active.required' => 'The :attribute field is required.',
            'is_active.boolean' => 'The :attribute field must be a boolean.',



            'priority.required' => 'The :attribute field is required.',
            'priority.string' => 'The :attribute field must be a string.',
            'priority.enum' => 'The :attribute field must be a valid enum value.',

            'media.array' => 'The :attribute must be an array.',
            'media.*.file' => 'Each media item must be a valid file.',
            'media.*.mimes' => 'Each media file must be one of: jpg, jpeg, png, webp, gif, pdf, txt.',
            'media.*.max' => 'Each media file size must not exceed 10MB.',

            'teamIds.array' => 'The :attribute must be an array.',
            'teamIds.*.integer' => 'Each team ID must be an integer.',
            'teamIds.*.exists' => 'Each team ID must exist in the teams table.',

            'start_date.required' => 'The :attribute field is required.',
            'start_date.date' => 'The :attribute field must be a valid date.',



        ];
    }

    public function attributes(): array
    {
        return [
            'name.ar' => 'Project Name (Arabic)',
            'name.en' => 'Project Name (English)',
            'description.ar' => 'Project Description (Arabic)',
            'description.en' => 'Project Description (English)',
            'is_active' => 'Project Activation',
            'status' => 'Project Status',
            'priority' => 'Project Priority',
            'media' => 'Project Media',
            'end_date' => 'Project End Date',
            'teamIds' => 'Project Teams',
            'start_date' => 'Project Start Date',
        ];
    }
}
