<?php

namespace App\Enums;

enum SupportCaseStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Resolved = 'resolved';
}
