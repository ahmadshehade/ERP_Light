<?php

namespace Modules\Tenant\Services\Task;

use App\Enums\NameOfRoles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\Tenant\Models\Task;
use Modules\Tenant\Models\TenantUser;
use Modules\Tenant\Notifications\Tasks\Actions\CancelTaskNotification;
use Modules\Tenant\Notifications\Tasks\Actions\CompleteTaskNotification;
use Modules\Tenant\Notifications\Tasks\Actions\OnHoldTaskNotification;
use Modules\Tenant\Notifications\Tasks\DeleteTaskNotification;
use Modules\Tenant\Notifications\Tasks\MakeNewTasksNotification;
use Modules\Tenant\Notifications\Tasks\RestoreTaskNotification;
use Modules\Tenant\Notifications\Tasks\UpdateTaskNotification;

class TaskNotificationService
{
    /**
     * Send notification when a new task is created.
     */
    public function addNewTaskNotify(Task $task): void
    {
        $recipients = $this->getRecipients($task);

        if ($recipients->isEmpty()) {
            return;
        }

        $data = $this->prepareData($task);

        Notification::send(
            $recipients,
            new MakeNewTasksNotification(
                $data['projectId'],
                $data['taskId'],
                $data['title_en'],
                $data['title_ar'],
                $data['description_en'],
                $data['description_ar'],
                $data['is_active'],
                $data['status'],
                $data['priority'],
                $data['company_name_en'],
                $data['company_name_ar']
            )
        );
    }

    /**
     * Send notification when a task is updated.
     */
    public function updateTaskNotify(Task $task): void
    {
        $recipients = $this->getRecipients($task);
        $data = $this->prepareData($task);
        Notification::send($recipients, new UpdateTaskNotification(
            $data['projectId'],
            $data['taskId'],
            $data['title_en'],
            $data['title_ar'],
            $data['description_en'],
            $data['description_ar'],
            $data['is_active'],
            $data['status'],
            $data['priority'],
            $data['company_name_en'],
            $data['company_name_ar']
        ));
    }


    /**
     * Summary of removeTaskNotify
     * @param array $data
     * @return void
     */
    public function removeTaskNotify(array $data): void
    {
        $recipients = $this->getRecipients($data['task']);
        Notification::send($recipients, new DeleteTaskNotification(
            $data['title_en'],
            $data['title_ar'],
            $data['task_id'],
            $data['copmany_name_en'],
            $data['copmany_name_ar']
        ));
    }


    /**
     * Summary of restoreNotify
     * @param  Task  $task
     * @return void
     */
    public function restoreNotify(Task $task): void
    {
        $recipients = $this->getRecipients($task);
        $data = $this->prepareData($task);
        Notification::send($recipients, new RestoreTaskNotification(
            $data['projectId'],
            $data['taskId'],
            $data['title_en'],
            $data['title_ar'],
            $data['description_en'],
            $data['description_ar'],
            $data['is_active'],
            $data['status'],
            $data['priority'],
            $data['company_name_en'],
            $data['company_name_ar']
        ));
    }

    public function completeTaskNotify(Task $task)
    {
        $recipients = $this->getRecipients($task);
        $data = $this->prepareData($task);
        Notification::send($recipients, new CompleteTaskNotification(
            $data['projectId'],
            $data['taskId'],
            $data['title_en'],
            $data['title_ar'],
            $data['description_en'],
            $data['description_ar'],
            $data['is_active'],
            $data['status'],
            $data['priority'],
            $data['start_date'],
            $data['due_date'],
            $data['completed_at'],
            $data['company_name_en'],
            $data['company_name_ar']
        ));
    }

    public function onHoldTaskNotify(Task $task)
    {
        $recipients = $this->getRecipients($task);
        $data = $this->prepareData($task);
        Notification::send($recipients, new OnHoldTaskNotification(
            $data['projectId'],
            $data['taskId'],
            $data['title_en'],
            $data['title_ar'],
            $data['description_en'],
            $data['description_ar'],
            $data['is_active'],
            $data['status'],
            $data['priority'],
            $data['start_date'],
            $data['due_date'],
            $data['completed_at'],
            $data['company_name_en'],
            $data['company_name_ar']
        ));
    }


    public function cancelTaskNotify(Task $task)
    {
        $recipients = $this->getRecipients($task);
        $data = $this->prepareData($task);
        Notification::send($recipients, new CancelTaskNotification(
            $data['projectId'],
            $data['taskId'],
            $data['title_en'],
            $data['title_ar'],
            $data['description_en'],
            $data['description_ar'],
            $data['is_active'],
            $data['status'],
            $data['priority'],
            $data['start_date'],
            $data['due_date'],
            $data['completed_at'],
            $data['company_name_en'],
            $data['company_name_ar']
        ));
    }

    /**
     * Get task notification recipients.
     */
    private function getRecipients(Task $task): Collection
    {
        $teamUsers = $task->team
            ->tenantUsers()
            ->with('user')
            ->get()
            ->pluck('user');

        $ownerUsers = TenantUser::role(NameOfRoles::Owner->value)
            ->with('user')
            ->get()
            ->pluck('user');

        return $ownerUsers
            ->merge($teamUsers)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Prepare notification data.
     */
    private function prepareData(Task $task): array
    {
        $company = tenant()->company;

        return [
            'projectId' => $task->project->id,
            'taskId' => $task->id,

            'title_en' => $task->getTranslation('title', 'en'),
            'title_ar' => $task->getTranslation('title', 'ar'),

            'description_en' => $task->getTranslation(
                'description',
                'en'
            ),

            'description_ar' => $task->getTranslation(
                'description',
                'ar'
            ),

            'is_active' => $task->is_active,
            'status' => $task->status->value,
            'priority' => $task->priority->value,

            'company_name_ar' => $company->getTranslation(
                'name',
                'ar'
            ),

            'company_name_en' => $company->getTranslation(
                'name',
                'en'
            ),

            'start_date' => $task->start_date ?? null,
            'due_date' => $task->due_date ?? null,
            'completed_at' => $task->completed_at ?? null
        ];
    }
}
