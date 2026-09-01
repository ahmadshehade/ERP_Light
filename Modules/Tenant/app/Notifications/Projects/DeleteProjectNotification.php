<?php

namespace Modules\Tenant\Notifications\Projects;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Enum\ProjectStatus;

class DeleteProjectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $name_en,
        public string $name_ar,
        public string $description_en,
        public string $description_ar,
        public ProjectStatus $status,
        public ProjectPriority $priority,
        public string $company_name_en,
        public string $company_name_ar,
        public bool $is_active,
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Project Deleted - ' . $this->name_en)

            ->greeting(
                'Hello ' . ($notifiable->user?->name ?? 'there') . ','
            )

            ->line(
                'A project has been deleted from your company.'
            )

            ->line('### Deleted Project')

            ->line('**Project:** ' . $this->name_en)

            ->line('**Description:** ' . $this->description_en)

            ->line('**Status:** ' . $this->status->value)

            ->line('**Priority:** ' . $this->priority->value)

            ->line(
                '**Project Status:** ' .
                    ($this->is_active ? 'Active' : 'Inactive')
            )

            ->line('**Company:** ' . $this->company_name_en)

            ->line(
                'The project and its associated information have been removed from the system.'
            )

            ->line(
                'If this deletion was not expected, please contact your company administrator.'
            )

            ->line('Thank you for using our platform.');
    }
}
