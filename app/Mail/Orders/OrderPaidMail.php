<?php

namespace App\Mail\Orders;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPaidMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Заказ №'.$this->order->number.' оплачен');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.orders.paid');
    }
}
