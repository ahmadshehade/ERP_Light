<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Projects;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Enum\ProjectStatus;

class UpdateProjectRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $project = $this->route('project');

        $this->merge([
            'name' => [
                'en' => $this->input('name_en')
                    ?? $project->getTranslation('name', 'en'),

                'ar' => $this->input('name_ar')
                    ?? $project->getTranslation('name', 'ar'),
            ],

            'description' => [
                'en' => $this->input('description_en')
                    ?? $project->getTranslation('description', 'en'),

                'ar' => $this->input('description_ar')
                    ?? $project->getTranslation('description', 'ar'),
            ],
        ]);
    }

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
                Rule::unique('projects', 'name->en')
                    ->ignore($this->route('project')->id),
            ],

            'name.ar' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('projects', 'name->ar')
                    ->ignore($this->route('project')->id),
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
                'sometimes',
                'boolean',
            ],
            'start_date' => [
                'sometimes',
                'date',
            ],

            'priority' => [
                'sometimes',
                'string',
                Rule::enum(ProjectPriority::class),
            ],
            'media' => [
                'sometimes',
                'array',
            ],

            'media.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,gif,pdf,txt',
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

    public function authorize(): bool
    {
        return $this->user()->can(
            'update',
            $this->route('project')
        );
    }

    public function messages(): array
    {
        return [

            'name.ar.string' =>
            'The :attribute field must be a string.',

            'name.ar.max' =>
            'The :attribute field must not exceed 255 characters.',

            'name.ar.unique' =>
            'The :attribute must be unique.',

            'name.en.string' =>
            'The :attribute field must be a string.',

            'name.en.max' =>
            'The :attribute field must not exceed 255 characters.',

            'name.en.unique' =>
            'The :attribute must be unique.',

            'description.en.string' =>
            'The :attribute field must be a string.',

            'description.en.max' =>
            'The :attribute field must not exceed 255 characters.',

            'description.ar.string' =>
            'The :attribute field must be a string.',

            'description.ar.max' =>
            'The :attribute field must not exceed 255 characters.',

            'is_active.boolean' =>
            'The :attribute field must be a boolean.',

            'start_date.date' => 'the :attribute field must be a date.',

            'priority.string' =>
            'The :attribute field must be a string.',

            'priority.enum' =>
            'The :attribute field must be a valid enum value.',

            'media.array' =>
            'The :attribute must be an array.',

            'media.*.file' =>
            'Each media item must be a valid file.',

            'media.*.mimes' =>
            'Each media file must be one of: jpg, jpeg, png, webp, gif, pdf, txt.',

            'media.*.max' =>
            'Each media file size must not exceed 10MB.',

            'teamIds.array' => 'The :attribute must be an array.',
            'teamIds.*.exists' => 'The teamIds.*.exists must be an exists.',
            'teamIds.*.integer' => 'The teamIds.*.integer must be an integer.',


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
            'teamIds' => 'Project Teams',
            'start_date' => 'Project Start Date',
        ];
    }
}
