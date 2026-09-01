<?php

namespace Modules\Tenant\Notifications\TenantUser;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateUserInTenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $tenantUserId,
        public string $name,
        public string $email,
        public bool $is_active,
        public string $company_name_en,
        public string $company_name_ar,
        public array $departments = [],
        public array $positions = [],
        public array $teams = [],
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $status = $this->is_active ? 'Active' : 'Inactive';

        $mail = (new MailMessage)
            ->subject('Tenant User Updated')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A tenant user has been updated.')
            ->line('Company: ' . $this->company_name_en)
            ->line('User: ' . $this->name)
            ->line('Email: ' . $this->email)
            ->line('Status: ' . $status);

        if (!empty($this->departments)) {
            $mail->line('Assigned Departments:');

            foreach ($this->departments as $department) {
                $mail->line('- ' . $department['name_en']);
            }
        } else {
            $mail->line('No departments are currently assigned to this user.');
        }
        if (!empty($this->positions)) {
            $mail->line('Assigned Positions:');
            foreach ($this->positions as $position) {
                $mail->line('- ' . $position['name_en']);
            }
        } else {
            $mail->line('No Positions are currently assigned to this user.');
        }
        if (!empty($teams)) {
            $mail->line('Assigned Teams:');
            foreach ($teams as $team) {
                $mail->line('- ' . $team['name_en'] . '/' . $team['name_ar']);
            }
        } else {
            $mail->line('No Teams are currently assigned to this user.');
        }


        return $mail->salutation('Regards, ' . config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            'tenant_user_id' => $this->tenantUserId,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,
            'departments' => $this->departments,
            'positions' => $this->positions,
            'teams' => $this->teams,
            'type' => self::class,
        ];
    }
}
