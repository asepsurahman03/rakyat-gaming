<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Selamat Datang di ' . config('app.name'))
            ->greeting('Halo ' . $notifiable->name . '!')
            ->line('Terima kasih telah mendaftar di ' . config('app.name') . '.')
            ->line('Kami senang Anda bergabung dengan komunitas gaming kami.')
            ->line('Di ' . config('app.name') . ', Anda dapat:')
            ->line('✅ Top up game favorit dengan harga terjangkau')
            ->line('✅ Proses cepat dan otomatis')
            ->line('✅ Berbagai metode pembayaran')
            ->line('✅ Customer support 24/7')
            ->action('Mulai Top Up', url('/'))
            ->line('Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi kami.')
            ->salutation('Salam Gaming, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
