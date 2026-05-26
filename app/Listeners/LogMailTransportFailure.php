<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class LogMailTransportFailure
{
    /**
     * Log Resend (or other transport) failures for debugging on cPanel / production.
     */
    public function __invoke(TransportExceptionInterface $exception): void
    {
        if (! in_array(config('mail.default'), ['resend', 'failover'], true)) {
            return;
        }

        $channel = config('mail.mailers.log.channel');

        $context = [
            'mailer' => config('mail.default'),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ];

        if ($channel) {
            Log::channel($channel)->error('Mail delivery failed via Resend', $context);
        } else {
            Log::error('Mail delivery failed via Resend', $context);
        }
    }
}
