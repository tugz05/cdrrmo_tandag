<?php

namespace App\Enums;

/**
 * Mobile app account kind (Flutter). Synced from Laratrust roles: staff vs user (citizen).
 */
enum AppMobileRole: string
{
    case Citizen = 'citizen';
    case Staff = 'staff';
}
