<?php

namespace App\Mail\Order;

use App\Models\Order;
use App\Traits\Mail\MailQueueTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use MailQueueTrait, Queueable, SerializesModels;

    public function __construct(public Order $order, public bool $isManualPayment = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your order :order is placed', ['order' => $this->order->public_order_id]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.order-placed',
        );
    }
}
