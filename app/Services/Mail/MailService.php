<?php

namespace App\Services\Mail;

use Illuminate\Contracts\Mail\MailQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

use function Illuminate\Support\defer;

class MailService
{
    public function send(string $to, Mailable $mail): void
    {
        $mailer = isMailQueueEnabled() ? Mail::mailer(mailQueueMailer()) : null;

        if (! $mailer instanceof MailQueue) {
            defer(fn () => Mail::to($to)->send($mail));

            return;
        }

        $mailer->queue($mail->to($to), mailQueueName());
    }
}
