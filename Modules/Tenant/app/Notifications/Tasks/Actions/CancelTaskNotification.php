<?php

namespace Modules\Tenant\Notifications\Tasks\Actions;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CancelTaskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $projectId,
        public int $taskId,
        public string $title_en,
        public string $title_ar,
        public string $description_en,
        public string $description_ar,
        public bool $is_active,
        public string $status,
        public string $priority,
        public ?Carbon $start_date,
        public ?Carbon $due_date,
        public ?Carbon $completed_at,
        public string $company_name_en,
        public string $company_name_ar,
    ) {}

    public function via($notifiable): array
    {
        return [
            'mail',
            'database',
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(
                "Task Cancelled - {$this->title_en}"
            )
            ->greeting(
                "Hello " . ($notifiable->name ?? '') . ","
            )
            ->line(
                "A task has been successfully cancelled in {$this->company_name_en}."
            )
            ->line(
                "Task: {$this->title_en}"
            )
            ->line(
                "Description: {$this->description_en}"
            )
            ->line(
                "Status: {$this->status}"
            )
            ->line(
                "Priority: {$this->priority}"
            )
            ->line(
                "Start Date: " .
                    ($this->start_date?->format('Y-m-d H:i') ?? 'N/A')
            )
            ->line(
                "Due Date: " .
                    ($this->due_date?->format('Y-m-d H:i') ?? 'N/A')
            )
            ->line(
                "Completed At: " .
                    ($this->completed_at?->format('Y-m-d H:i') ?? 'N/A')
            )
            ->action(
                'View Task',
                url("/tasks/{$this->taskId}")
            )
            ->line(
                "Thank you for using {$this->company_name_en}."
            );
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => self::class,

            'project_id' => $this->projectId,
            'task_id' => $this->taskId,

            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,

            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,

            'is_active' => $this->is_active,

            'status' => $this->status,
            'priority' => $this->priority,

            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,

            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,
        ];
    }
}
