<?php

use App\Mail\Order\OrderPlacedMail;
use App\Models\Order;
use App\Services\Mail\MailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

test('order mail is sent right after the response when the mail queue is off', function () {
    config(['mail.queue.enabled' => false]);
    Mail::fake();
    $this->withoutDefer();

    app(MailService::class)->send('buyer@example.com', new OrderPlacedMail(Order::factory()->create()));

    Mail::assertSent(OrderPlacedMail::class, fn (OrderPlacedMail $mail): bool => $mail->hasTo('buyer@example.com'));
    Mail::assertNothingQueued();
});

test('order mail is queued when the mail queue is on', function () {
    config(['mail.queue.enabled' => true]);
    Mail::fake();

    app(MailService::class)->send('buyer@example.com', new OrderPlacedMail(Order::factory()->create()));

    Mail::assertQueued(OrderPlacedMail::class, fn (OrderPlacedMail $mail): bool => $mail->hasTo('buyer@example.com') && $mail->mailer === 'smtp');
    Mail::assertNothingSent();
});

test('queued mail is retried until the mail server is back', function () {
    config([
        'mail.queue.enabled' => true,
        'queue.default' => 'database',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
        'mail.mailers.smtp.timeout' => 1,
    ]);

    app(MailService::class)->send('buyer@example.com', new OrderPlacedMail(Order::factory()->create()));

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

    expect(DB::table('jobs')->value('attempts'))->toBe(1)
        ->and(DB::table('failed_jobs')->count())->toBe(0);

    config(['mail.mailers.smtp' => ['transport' => 'array']]);
    Mail::purge('smtp');
    $this->travel(mailQueueBackoffSeconds() + 1)->seconds();

    $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0)
        ->and(Mail::mailer('smtp')->getSymfonyTransport()->messages())->toHaveCount(1);
});
