<?php

namespace App\Traits\Mail;

use DateTimeInterface;
use Illuminate\Support\Facades\Date;

trait MailQueueTrait
{
    public function backoff(): int
    {
        return mailQueueBackoffSeconds();
    }

    public function retryUntil(): DateTimeInterface
    {
        return Date::now()->addHours(mailQueueRetryHours());
    }
}
