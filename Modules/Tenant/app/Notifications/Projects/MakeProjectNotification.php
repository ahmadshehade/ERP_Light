<?php

namespace Modules\Tenant\Notifications\Projects;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Tenant\Enum\ProjectPriority;
use Modules\Tenant\Enum\ProjectStatus;

class MakeProjectNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
            ->subject("New Project Created - {$this->name_en}")

            ->greeting("Hello {$notifiable->user?->name},")

            ->line(
                "A new project has been created in {$this->company_name_en}."
            )

            ->line("**Project:** {$this->name_en}")

            ->line("**Description:** {$this->description_en}")

            ->line("**Status:** {$this->status->value}")

            ->line("**Priority:** {$this->priority->value}")

            ->line(
                $this->is_active
                    ? 'The project is currently active.'
                    : 'The project is currently inactive.'
            )


            ->action(
                'View Project',
                url('/api/v1/projects')
            )

            ->line('Thank you for using our platform.');
        if (!empty($this->teams)) {
            $mail->line('Teams in Project :');
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
            'type' => 'project.created',

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

            'notification' => self::class,
        ];
    }
}
