<?php

namespace Modules\Tenant\Http\Requests\Api\V1\Tasks;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Modules\Tenant\Enum\TaskPriority;
use Modules\Tenant\Enum\TenantRoles;
use Modules\Tenant\Models\TenantUser;

class UpdateTaskRequest extends BaseRequest
{
    /**
     * Prepare the data for validation.
     *@return void
     */
    public function prepareForValidation(): void
    {
        $task = $this->route('task');
        $this->merge([
            'title' => [
                'en' => $this->input('title_en') ?? $task->getTranslation('title', 'en'),
                'ar' => $this->input('title_ar') ?? $task->getTranslation('title', 'ar'),
            ],

            'description' => [
                'en' => $this->input('description_en') ?? $task->getTranslation('description', 'en'),
                'ar' => $this->input('description_ar') ?? $task->getTranslation('description', 'ar'),
            ],
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $task = $this->route('task');

        $rules = [
            'project_id' => [
                'sometimes',
                'integer',
                'exists:projects,id',
            ],

            'team_id' => [
                'sometimes',
                'integer',
                'exists:teams,id',
            ],

            'title' => [
                'sometimes',
                'array',
            ],

            'title.en' => [
                'sometimes',
                'required_with:title',
                'string',
                'max:255',
                Rule::unique('tenant.tasks', 'title->en')
                    ->ignore($task?->id),
            ],

            'title.ar' => [
                'sometimes',
                'required_with:title',
                'string',
                'max:255',
                Rule::unique('tenant.tasks', 'title->ar')
                    ->ignore($task?->id),
            ],

            'description' => [
                'sometimes',
                'array',
            ],

            'description.en' => [
                'sometimes',
                'required_with:description',
                'string',
                'max:255',
            ],

            'description.ar' => [
                'sometimes',
                'required_with:description',
                'string',
                'max:255',
            ],

            'start_date' => [
                'sometimes',
                'date',
            ],

            'due_date' => [
                'sometimes',
                'date',
                'after_or_equal:start_date',
            ],

            'priority' => [
                'sometimes',
                Rule::enum(TaskPriority::class),
            ],
            'media' => [
                'sometimes',
                'array',
            ],

            'media.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,gif,pdf,txt,tex,rar,doc,docx,xls,xlsx,ppt,pptx,csv,odt,ods,odp,odg,odf,ott,otp,ottm,otg,otp,ots,otp,ottt,ottm,ottg,ottp,ottt,ottm,ottg,ottp',
                'max:10240',
            ],
        ];
        $user = $this->user();
        $tenatnUser = TenantUser::where('user_id', $user->id)->first();
        if ($tenatnUser->hasRole(TenantRoles::Owner->value)) {
            $rules['is_active'] = [
                'sometimes',
                'boolean',
            ];
        }
        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'project_id.integer' => 'The project ID must be an integer.',
            'project_id.exists' => 'The selected project does not exist.',

            'team_id.integer' => 'The team ID must be an integer.',
            'team_id.exists' => 'The selected team does not exist.',

            'title.array' => 'The title must be provided in English and Arabic.',

            'title.en.required_with' => 'The English title is required when updating the title.',
            'title.en.string' => 'The English title must be a string.',
            'title.en.max' => 'The English title may not exceed 255 characters.',
            'title.en.unique' => 'The English title has already been taken.',

            'title.ar.required_with' => 'The Arabic title is required when updating the title.',
            'title.ar.string' => 'The Arabic title must be a string.',
            'title.ar.max' => 'The Arabic title may not exceed 255 characters.',
            'title.ar.unique' => 'The Arabic title has already been taken.',

            'description.array' => 'The description must be provided in English and Arabic.',

            'description.en.required_with' => 'The English description is required when updating the description.',
            'description.en.string' => 'The English description must be a string.',
            'description.en.max' => 'The English description may not exceed 255 characters.',

            'description.ar.required_with' => 'The Arabic description is required when updating the description.',
            'description.ar.string' => 'The Arabic description must be a string.',
            'description.ar.max' => 'The Arabic description may not exceed 255 characters.',

            'start_date.date' => 'The start date must be a valid date.',

            'due_date.date' => 'The due date must be a valid date.',
            'due_date.after_or_equal' => 'The due date must be after or equal to the start date.',

            'priority.enum' => 'The selected priority is invalid.',

            'is_active.boolean' => 'The active status must be true or false.',

            'media.array' => 'The media must be an array.',
            'media.*.file' => 'Each media item must be a valid file.',
            'media.*.mimes' => 'Each media item must be a valid file type.',
            'media.*.max' => 'Each media item may not exceed 10240 kilobytes.',
        ];
    }

    /**
     * Get custom attribute names.
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
            'media' => 'media',
            'media.*' => 'media item',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task
            && $this->user()->can('update', $task);
    }
}
