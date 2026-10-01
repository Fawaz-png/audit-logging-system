<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivateAccount extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('account.activate', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);

        return (new MailMessage)
            ->subject('Activate your IS Audit account')
            ->greeting('Welcome, '.$notifiable->name)
            ->line('Your IS Audit account has been created.')
            ->line('Use the button below to activate your account and set your password.')
            ->action('Activate Account', $url)
            ->line('This activation link will expire according to the configured password reset token expiry period.');
    }
}
