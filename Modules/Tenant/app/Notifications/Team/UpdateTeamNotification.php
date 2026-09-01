<?php

namespace Modules\Tenant\Notifications\Team;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpdateTeamNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
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
            ->subject("Team Updated - {$this->company_name_en}")

            ->greeting("Hello {$notifiable->name},")

            ->line(
                "A team in your company has been updated successfully."
            )

            ->line("### Updated Team Information")

            ->line("**Team Name:** {$this->name_en}")

            ->line(
                "**Description:** " .
                    ($this->description_en ?: 'No description provided.')
            )

            ->line(
                "**Status:** " .
                    ($this->is_active ? 'Active' : 'Inactive')
            )

            ->line("**Company:** {$this->company_name_en}")

            ->line(
                "The team information has been updated and the new changes are now available."
            )

            ->action('View Teams', url('/teams'))

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
            ->subject("تم تحديث الفريق - {$this->company_name_ar}")

            ->greeting("مرحباً {$notifiable->name}،")

            ->line(
                "تم تحديث أحد الفرق في شركتك بنجاح."
            )

            ->line("### معلومات الفريق المحدثة")

            ->line("**اسم الفريق:** {$this->name_ar}")

            ->line(
                "**الوصف:** " .
                    ($this->description_ar ?: 'لم يتم إضافة وصف.')
            )

            ->line(
                "**الحالة:** " .
                    ($this->is_active ? 'نشط' : 'غير نشط')
            )

            ->line("**الشركة:** {$this->company_name_ar}")

            ->line(
                "تم تحديث معلومات الفريق وأصبحت التغييرات الجديدة متاحة الآن."
            )

            ->action('عرض الفرق', url('/teams'))

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
