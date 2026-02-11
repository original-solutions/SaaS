<?php

namespace App\Enums;

enum LoginEventType: string
{
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
}
