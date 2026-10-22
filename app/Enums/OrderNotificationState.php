<?php

namespace App\Enums;

enum OrderNotificationState: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Suppressed = 'suppressed';
    case Uncertain = 'uncertain';
}
