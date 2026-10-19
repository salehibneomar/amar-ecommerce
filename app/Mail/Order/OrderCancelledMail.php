<?php

namespace App\Mail\Order;

use App\Models\Order;
use App\Traits\Mail\MailQueueTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use MailQueueTrait, Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your order :order was cancelled', ['order' => $this->order->public_order_id]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-cancelled',
        );
    }
}
