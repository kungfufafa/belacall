<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginMagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(public string $loginUrl) {}

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
            ->subject('Magic Link Login Admin')
            ->greeting('Halo,')
            ->line('Klik tombol di bawah untuk masuk ke panel admin.')
            ->line('Tautan ini hanya berlaku selama 15 menit dan hanya bisa digunakan 1 kali.')
            ->action('Masuk ke Admin', $this->loginUrl)
            ->line('Jika Anda tidak meminta login, abaikan email ini.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'login_url' => $this->loginUrl,
        ];
    }
}
