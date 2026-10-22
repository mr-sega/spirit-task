<?php

namespace App\Enums;

enum OrderNotificationType: string
{
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';
}
