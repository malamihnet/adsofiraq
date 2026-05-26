<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    /**
     * Build the branded Ads of Iraq verification email.
     */
    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirm your Ads of Iraq account')
            ->view('emails.verify-email', [
                'url' => $url,
                'user' => $notifiable,
            ]);
    }
}
