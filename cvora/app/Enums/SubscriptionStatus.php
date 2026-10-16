<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Replaced = 'replaced';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
