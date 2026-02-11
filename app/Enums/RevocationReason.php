<?php

namespace App\Enums;

enum RevocationReason: string
{
    case Logout = 'logout';
    case PasswordReset = 'password_reset';
    case RefreshReuseDetected = 'refresh_reuse_detected';
    case AdminRevoked = 'admin_revoked';
}
