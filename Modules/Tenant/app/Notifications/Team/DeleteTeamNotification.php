<?php

namespace Modules\Tenant\Notifications\Team;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeleteTeamNotification extends Notification implements ShouldQueue
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
        public bool $is_active,
        public string $company_name_en,
        public string $company_name_ar
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
        $language = $notifiable->language ?? 'en';

        return $language === 'ar'
            ? $this->arabicMail($notifiable)
            : $this->englishMail($notifiable);
    }

    /**
     * English email notification.
     */
    protected function englishMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Team Deleted - {$this->company_name_en}")

            ->greeting("Hello {$notifiable->name},")

            ->line(
                "A team has been deleted from your company."
            )

            ->line("### Deleted Team Information")

            ->line("**Team Name:** {$this->name_en}")

            ->line(
                "**Description:** " .
                    ($this->description_en ?: 'No description provided.')
            )

            ->line(
                "**Status Before Deletion:** " .
                    ($this->is_active ? 'Active' : 'Inactive')
            )

            ->line("**Company:** {$this->company_name_en}")

            ->line(
                "This team is no longer available in your company's system."
            )

            ->line(
                'If this action was not expected, please contact your system administrator.'
            )

            ->line(
                'Thank you for using our platform.'
            );
    }

    /**
     * Arabic email notification.
     */
    protected function arabicMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("تم حذف الفريق - {$this->company_name_ar}")

            ->greeting("مرحباً {$notifiable->name}،")

            ->line(
                "تم حذف أحد الفرق من شركتك."
            )

            ->line("### معلومات الفريق المحذوف")

            ->line("**اسم الفريق:** {$this->name_ar}")

            ->line(
                "**الوصف:** " .
                    ($this->description_ar ?: 'لم يتم إضافة وصف.')
            )

            ->line(
                "**الحالة قبل الحذف:** " .
                    ($this->is_active ? 'نشط' : 'غير نشط')
            )

            ->line("**الشركة:** {$this->company_name_ar}")

            ->line(
                "لم يعد هذا الفريق متاحاً في نظام شركتك."
            )

            ->line(
                "إذا لم تكن تتوقع تنفيذ هذا الإجراء، يرجى التواصل مع مسؤول النظام."
            )

            ->line(
                'شكراً لاستخدامك منصتنا.'
            );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,

            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,

            'is_active' => $this->is_active,

            'company_name_en' => $this->company_name_en,
            'company_name_ar' => $this->company_name_ar,
        ];
    }
}
