<?php

namespace Modules\Central\Notifications\Api\V1\Companies;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeleteCompanyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $companyId,
        public string $companyNameEn,
        public string $companyNameAr,
        public string $subDomain,
        public int $maxUsers,
        public bool $isActive,
        public User $owner,
        public Carbon $deletedAt
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Company Deleted Successfully')
            ->greeting('Hello ' . ($notifiable->name ?? ''))
            ->line('A  company has been Deleted successfully.')
            ->line('Company Deleted At: ' . $this->deletedAt->format('Y-m-d h:i A'))
            ->line('Owner Name: ' . $this->owner->name)
            ->line('Company Name (English): ' . $this->companyNameEn)
            ->line('Company Name (Arabic): ' . $this->companyNameAr)
            ->line('Subdomain: ' . $this->subDomain)
            ->line('Maximum Users: ' . $this->maxUsers)
            ->line('Status: ' . ($this->isActive ? 'Active' : 'Inactive'))
            ->line('Company ID: ' . $this->companyId);
    }

    public function toArray($notifiable): array
    {
        return [
            'company_id' => $this->companyId,
            'owner_name' => $this->owner->name,
            'company_name_en' => $this->companyNameEn,
            'company_name_ar' => $this->companyNameAr,
            'sub_domain' => $this->subDomain,
            'max_users' => $this->maxUsers,
            'is_active' => $this->isActive,
            'message' => 'A  company has been Deleted successfully.',
        ];
    }
}
