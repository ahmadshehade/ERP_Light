<?php

namespace Modules\Tenant\Notifications\TenantUser;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RemoveUserFromTenantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $tenantUserId,
        public string $name,
        public string $email,
        public string $company_name_en,
        public string $company_name_ar,
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('User Removed from Company')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A user has been removed from your company.')
            ->line('Company: ' . $this->company_name_en)
            ->line('User: ' . $this->name)
            ->line('Email: ' . $this->email)
            ->line(
                'The user no longer has access to this company.'
            )
            ->salutation('Regards, ' . config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            'tenant_user_id' => $this->tenantUserId,
            'name' => $this->name,
            'email' => $this->email,
            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,
            'type' => self::class,
        ];
    }
}
