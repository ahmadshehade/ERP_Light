<?php

namespace Modules\Tenant\Notifications\TenantUser;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddUserToTenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $tenantUserId,
        public string $name,
        public string $email,
        public bool $isActive,
        public string $companyNameEn,
        public string $companyNameAr,
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
        $statusEn = $this->isActive ? 'Active' : 'Inactive';
        $mail = (new MailMessage)
            ->subject('New User Added to Company')
            ->greeting(
                'Hello ' . ($notifiable->name ?? '')
            )
            ->line(
                'A new user has been added to your company.'
            )
            ->line(
                'Company: ' . $this->companyNameEn
            )
            ->line(
                'User: ' . $this->name
            )
            ->line(
                'Email: ' . $this->email
            )
            ->line(
                'Status: ' . $statusEn
            );
        if (!empty($this->departments)) {
            $mail->line('Assigned Departments:');
            foreach ($this->departments as $department) {
                $mail->line(
                    $department['name_en']
                        . ' / '
                        . $department['name_ar']
                );
            }
        } else {
            $mail->line(
                'No departments have been assigned to this user.'
            );
        }
        if (!empty($this->positions)) {
            $mail->line('Assigned Positions:');
            foreach ($this->positions as $position) {
                $mail->line(
                    $position['name_en']
                        . ' / '
                        . $position['name_ar']
                );
            }
        } else {
            $mail->line(
                'No positions have been assigned to this user.'
            );
        }
        if (!empty($this->teams)) {
            $mail->line('Assigned Teams:');
            foreach ($this->teams as $team) {
                $mail->line(
                    $team['name_en']
                        . ' / '
                        . $team['name_ar']
                );
            }
        } else {
            $mail->line(
                'No teams have been assigned to this user.'
            );
        }
        return $mail->salutation(
            'Regards, ' . config('app.name')
        );
    }

    public function toArray($notifiable): array
    {
        return [
            'tenant_user_id' => $this->tenantUserId,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'company_name_en' => $this->companyNameEn,
            'company_name_ar' => $this->companyNameAr,
            'departments' => $this->departments,
            'positions' => $this->positions,
            'type' => self::class,
        ];
    }
}
