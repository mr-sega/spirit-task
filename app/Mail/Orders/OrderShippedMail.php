<?php

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderShippedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Заказ №'.$this->order->number.' передан в доставку');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.orders.shipped');
    }
}
