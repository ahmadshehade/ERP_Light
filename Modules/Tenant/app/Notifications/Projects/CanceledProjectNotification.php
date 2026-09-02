<?php

namespace Modules\Tenant\Notifications\Projects;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Enum\ProjectStatus;

class CanceledProjectNotification extends Notification implements ShouldQueue
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
        public ?Carbon $start_date,
        public ?Carbon $end_date,
        public array $teams = [],

    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Project Canceled - ' . $this->name_en)

            ->greeting('Hello ' . ($notifiable->user?->name ?? 'there') . ',')

            ->line('A project has been canceled.')
            ->line('### Project Information')

            ->line('**Project:** ' . $this->name_en)

            ->line('**Description:** ' . $this->description_en)

            ->line('**Status:** ' . $this->status->value)

            ->line('**Priority:** ' . $this->priority->value)
            ->line(
                '**Project StartDate:** ' .
                    ($this->start_date?->format('Y-m-d') ?? 'Not set')
            )->line(
                '**Project EndDate:** ' .
                    ($this->end_date?->format('Y-m-d') ?? 'Not set')
            )
            ->line(
                '**Status:** ' .
                    ($this->is_active ? 'Active' : 'Inactive')
            )

            ->line('**Company:** ' . $this->company_name_en)

            ->line('The project information has been updated successfully.')

            ->line('Thank you for using our platform.');

        if (!empty($this->teams)) {
            foreach ($this->teams as $team) {
                $mail->line('Team Name:' . $team['team_name_en'] .
                    '/' . $team['team_name_ar']);
            }
        }
        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'project.canceled',
            'name' => [
                'en' => $this->name_en,
                'ar' => $this->name_ar,
            ],
            'description' => [
                'en' => $this->description_en,
                'ar' => $this->description_ar,
            ],
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'company_name' => [
                'en' => $this->company_name_en,
                'ar' => $this->company_name_ar,
            ],
            'is_active' => $this->is_active,
            'start_date' => $this->start_date ?? null,
            'end_date' => $this->end_date ?? null,
            'notification' => self::class,
        ];
    }
}
