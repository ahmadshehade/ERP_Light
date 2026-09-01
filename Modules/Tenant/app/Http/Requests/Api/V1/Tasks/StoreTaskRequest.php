<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Tasks;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Enum\TaskPriority;
use Modules\Tenant\Models\Task;
use Override;

class StoreTaskRequest extends BaseRequest
{
    #[Override]
    public function prepareForValidation(): void
    {
        $this->merge([
            'title' => [
                'en' => $this->input('title_en'),
                'ar' => $this->input('title_ar'),
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
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id',
            ],

            'team_id' => [
                'required',
                'integer',
                'exists:teams,id',
            ],

            'title' => [
                'required',
                'array',
            ],

            'title.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant.tasks', 'title->en'),
            ],

            'title.ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tenant.tasks', 'title->ar'),
            ],

            'description' => [
                'required',
                'array',
            ],

            'description.en' => [
                'required',
                'string',
                'max:255',
            ],

            'description.ar' => [
                'required',
                'string',
                'max:255',
            ],

            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'due_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'priority' => [
                'required',
                Rule::enum(TaskPriority::class),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        return $this->user()->can('create', Task::class);
    }


    /**
     * Get the error messages for the defined validation rules.
     * @return array
     */
    public function messages(): array
    {
        return [
            'project_id.required' => 'The project is required.',
            'project_id.integer' => 'The project ID must be an integer.',
            'project_id.exists' => 'The selected project does not exist.',

            'team_id.required' => 'The team is required.',
            'team_id.integer' => 'The team ID must be an integer.',
            'team_id.exists' => 'The selected team does not exist.',

            'title.required' => 'The title is required.',
            'title.array' => 'The title must be provided in English and Arabic.',

            'title.en.required' => 'The English title is required.',
            'title.en.string' => 'The English title must be a string.',
            'title.en.max' => 'The English title may not exceed 255 characters.',
            'title.en.unique' => 'The English title has already been taken.',

            'title.ar.required' => 'The Arabic title is required.',
            'title.ar.string' => 'The Arabic title must be a string.',
            'title.ar.max' => 'The Arabic title may not exceed 255 characters.',
            'title.ar.unique' => 'The Arabic title has already been taken.',

            'description.required' => 'The description is required.',
            'description.array' => 'The description must be provided in English and Arabic.',

            'description.en.required' => 'The English description is required.',
            'description.en.string' => 'The English description must be a string.',
            'description.en.max' => 'The English description may not exceed 255 characters.',

            'description.ar.required' => 'The Arabic description is required.',
            'description.ar.string' => 'The Arabic description must be a string.',
            'description.ar.max' => 'The Arabic description may not exceed 255 characters.',

            'start_date.required' => 'The start date is required.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.after.or_equal' => 'The start date must be after or equal to today.',

            'due_date.required' => 'The due date is required.',
            'due_date.date' => 'The due date must be a valid date.',
            'due_date.after_or_equal' => 'The due date must be after or equal to the start date.',

            'priority.required' => 'The priority is required.',
            'priority.enum' => 'The selected priority is invalid.',

            'is_active.required' => 'The active status is required.',
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }


    /**
     * Get custom attributes for validator errors.
     * @return array
     */
    public function attributes(): array
    {
        return [
            'project_id' => 'project',
            'team_id' => 'team',

            'title' => 'title',
            'title.en' => 'English title',
            'title.ar' => 'Arabic title',

            'description' => 'description',
            'description.en' => 'English description',
            'description.ar' => 'Arabic description',

            'start_date' => 'start date',
            'due_date' => 'due date',

            'priority' => 'priority',

            'is_active' => 'active status',
        ];
    }
}
