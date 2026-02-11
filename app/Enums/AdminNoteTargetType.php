<?php

namespace App\Enums;

enum AdminNoteTargetType: string
{
    case User = 'user';
    case Tenant = 'tenant';
}
